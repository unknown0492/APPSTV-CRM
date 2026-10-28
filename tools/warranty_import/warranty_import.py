"""
Warranty Excel import generator (appstv_crm v1.0.5)

Reads one sheet of the "TV Warranty Record" workbook and writes:
  - a SQL migration (all-or-nothing transaction, every INSERT guarded by NOT EXISTS on its ID) that the server
    runs with `appstv migrate <file>` (the deploy script backs the database up first),
  - an undo SQL that removes exactly what the migration added,
  - a dry-run report to review before anything is committed.

Nothing is written to any database by this script. It works from a JSON snapshot of the database
(tools/warranty_import/snapshot_export.php, read-only).

Sheet columns (row 2 = headers, data from row 3):
  A Date of Delivery | B Warranty Expired | C/D Extended Warranty Start/End ('Nil' or dates) | E Company Name /Location
  F Invoice No. | G Invoice Date | H TV Model | I Serial Number
  J-M Track Record 1: Date of Service, New Serial Number, Remark, Ref No.   N-Q Track Record 2 (same)

Rules (agreed 2026-10-28):
  - Duplicate serial numbers are real (a manufacturer batch arrived with duplicates): every sheet row is its own TV.
    An existing record is reused only when it is stock (not allotted, not invoiced, not in an order, not a
    replacement) under the same product; otherwise a new TV record is created.
  - A TV model name that is not mapped below stops the run: new models must be confirmed (name / SKU) and added
    to NEW_PRODUCTS first.
  - Warranty: start = Date of Delivery, period = days until Warranty Expired. Extended warranty: start / period
    from columns C / D.
  - Service event with a New Serial Number = replacement (is_repairable 0) with a new TV of the same product;
    without one = repair (is_repairable 1). Ref No. = service invoice for the same customer (an existing invoice
    with that number is reused). A replacement TV carries the rest of the original warranty through the history
    chain, so it gets no warranty record of its own.

Usage:
  python -I warranty_import.py --excel "TV Warranty Record.xlsx" --sheet "Data Sheet_Not Upload to System"
                               --snapshot snapshot.json --name 2026-10-28_warranty_import_tv_record
                               --migrations <repo>/migrations --report report.txt
"""
import argparse, collections, datetime, json, random, sys
import openpyxl

# ── Product of each TV model name in the sheet ─────────────────────────────────────────────────────────────────────
MODEL_PRODUCT = {
    'VQ43"':    '1030242096',     # APPSTV QLED VQ Series 4K UHD 43"
    'VQ50"':    '1030242464',     # APPSTV QLED VQ Series 4K UHD 50"
    'VQ55"':    '1030242019',     # APPSTV QLED VQ Series 4K UHD 55"
    'VH32"':    '0510256565',     # APPSTV VH Series 32" TV
    'VH43"':    '0510252708',     # APPSTV VH Series 43" TV
    'VH50"':    '0510251732',     # APPSTV VH Series 50" TV (no smart OS)
    'esTV 55"': '0324268998',     # esTV 55" inch (has_sn switched on by this import)
    'VH50S':    'NEW:103150',     # Google TV model, created below
    'VQ75':     'NEW:101075',     # Created below
}
# New products, confirmed by the owner 2026-10-28 (key = SKU)
NEW_PRODUCTS = {
    '103150': 'APPSTV VH Series 50" Google TV',
    '101075': 'APPSTV QLED VQ Series 4K UHD 75"',
}
# Existing products whose has_sn must be switched on because serial numbers are now attached
SWITCH_ON_HAS_SN = ['0324268998']

CUSTOMER_SOURCE_DIRECT = '20365841'                     # sources.source_id of "Direct" (same as the last import)
WARRANTY_REMARK        = 'Imported from TV Warranty Record.xlsx'
EXT_REMARK             = 'Extended warranty, imported from TV Warranty Record.xlsx'


def sql_str(value):
    """@description Quotes a value for SQL (NULL for None) @param value str|None @returns str"""
    if value is None:
        return 'NULL'
    return "'" + str(value).replace('\\', '\\\\').replace("'", "''") + "'"


def norm_sn(value):
    """@description Serial number as text (Excel numbers lose nothing), trimmed, upper-case @returns str"""
    if value is None:
        return ''
    if isinstance(value, float) and value.is_integer():
        value = int(value)
    return str(value).strip().upper()


def dt_sql(d):
    """@description Excel date -> 'YYYY-MM-DD 00:00:00' (format already used in the database) @returns str"""
    return d.strftime('%Y-%m-%d 00:00:00')


class IdMaker:
    """Makes IDs in the system's own shapes, unique against the database snapshot and each other."""

    def __init__(self, snapshot_ids, now, seed):
        self.taken = set()
        for ids in snapshot_ids.values():
            self.taken.update(str(i) for i in ids)
        self.now = now
        self.rng = random.Random(seed)

    def _unique(self, make):
        while True:
            value = make()
            if value not in self.taken:
                self.taken.add(value)
                return value

    def customer(self):     # ddmmyy + 4 digits (Customer::generateCustomerID)
        return self._unique(lambda: self.now.strftime('%d%m%y') + '%04d' % self.rng.randrange(10000))

    def product(self):      # mmddyy + 4 digits (Product::generateProductID)
        return self._unique(lambda: self.now.strftime('%m%d%y') + '%04d' % self.rng.randrange(10000))

    def product_sn(self):   # dd + 3 + 5 digits (Product::generateProductSnID)
        return self._unique(lambda: self.now.strftime('%d') + '%03d' % self.rng.randrange(1000) + '%05d' % self.rng.randrange(100000))

    def record(self):       # dd + 4 + 4 digits (invoice, ipsn, psnw, psnexw, pwrp generators)
        return self._unique(lambda: self.now.strftime('%d') + '%04d' % self.rng.randrange(10000) + '%04d' % self.rng.randrange(1000, 10000))


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--excel', required=True)
    ap.add_argument('--sheet', required=True)
    ap.add_argument('--snapshot', required=True)
    ap.add_argument('--name', required=True, help='Migration name, e.g. 2026-10-28_warranty_import_tv_record')
    ap.add_argument('--migrations', required=True, help='The repo migrations folder')
    ap.add_argument('--report', required=True)
    ap.add_argument('--seed', default='appstv-warranty-import')
    args = ap.parse_args()

    snap = json.load(open(args.snapshot, encoding='utf-8'))
    now  = datetime.datetime.now(datetime.timezone(datetime.timedelta(hours=8))).replace(tzinfo=None)   # Singapore time, like the system
    stamp = now.strftime('%Y-%m-%d %H:%M:%S')
    ids  = IdMaker(snap['ids'], now, args.seed)

    # ── Database lookups ──────────────────────────────────────────────────────────────────────────────────────────
    customer_by_name = {}
    for c in snap['customers']:
        customer_by_name.setdefault(c['company_name'].strip().lower(), c['customer_id'])
    invoice_by_no = {}
    for i in snap['invoices']:
        invoice_by_no.setdefault(str(i['invoice_no']).strip(), i)
    products = {p['product_id']: p for p in snap['products']}
    stock = collections.defaultdict(list)          # (serial, product_id) -> reusable stock TV records
    for ps in snap['product_sn']:
        free = (str(ps['allotted_to_customer']) == '0' and str(ps['invoiced']) == '0'
                and str(ps['in_order']) == '0' and str(ps['is_replacement']) == '0')
        if free:
            stock[(ps['serial_number'].strip().upper(), ps['product_id'])].append(ps['product_sn_id'])

    # ── Read the sheet ────────────────────────────────────────────────────────────────────────────────────────────
    wb = openpyxl.load_workbook(args.excel, data_only=True, read_only=True)
    rows = []
    for n, r in enumerate(wb[args.sheet].iter_rows(min_row=3, values_only=True), start=3):
        r = list(r) + [None] * (17 - len(r))
        if not r[8] and not r[5]:
            continue                                       # Empty or stray rows (no serial, no invoice)
        rows.append((n, r))

    unknown = sorted({str(r[7]) for _, r in rows if r[7] not in MODEL_PRODUCT})
    if unknown:
        sys.exit('STOP: TV model(s) not mapped, confirm and add them first: ' + ', '.join(unknown))

    # ── Plan ──────────────────────────────────────────────────────────────────────────────────────────────────────
    sql, undo = [], collections.defaultdict(list)
    rep = collections.Counter()
    notes = []
    new_customers, new_invoices, new_products = {}, {}, {}
    reused, created_tvs = [], []

    def product_of(model):
        target = MODEL_PRODUCT[model]
        if not target.startswith('NEW:'):
            return target
        sku = target[4:]
        if sku not in new_products:
            new_products[sku] = ids.product()
        return new_products[sku]

    def customer_of(name):
        key = name.strip().lower()
        if key in customer_by_name:
            return customer_by_name[key]
        if key not in new_customers:
            new_customers[key] = (ids.customer(), name.strip())
        return new_customers[key][0]

    def invoice_of(no, date, customer_id):
        no = str(no).strip()
        if no in invoice_by_no:
            return invoice_by_no[no]['invoice_id'], False
        if no not in new_invoices:
            new_invoices[no] = (ids.record(), date, customer_id)
        return new_invoices[no][0], True

    def tv_for(serial, product_id, row_no, why):
        pool = stock.get((serial, product_id))
        if pool:
            psn = pool.pop(0)
            reused.append((psn, serial, row_no, why))
            return psn
        psn = ids.product_sn()
        created_tvs.append((psn, product_id, serial, row_no, why))
        return psn

    tv_rows = []
    for n, r in rows:
        serial  = norm_sn(r[8])
        prod    = product_of(r[7])
        cust    = customer_of(str(r[4]))
        inv, _  = invoice_of(r[5], dt_sql(r[6]), cust)
        psn     = tv_for(serial, prod, n, 'sold')
        psnw    = ids.record()
        period  = (r[1] - r[0]).days
        ext     = None
        if isinstance(r[2], datetime.datetime) and isinstance(r[3], datetime.datetime):
            ext = (ids.record(), dt_sql(r[2]), (r[3] - r[2]).days)
        events = []
        for base in (9, 13):
            if not isinstance(r[base], datetime.datetime):
                continue
            new_sn  = norm_sn(r[base + 1])
            remark  = (str(r[base + 2]).strip() if r[base + 2] is not None else '')
            ref     = r[base + 3]
            new_psn = tv_for(new_sn, prod, n, 'replacement') if new_sn else None
            new_inv = invoice_of(ref, dt_sql(r[base]), cust)[0] if ref not in (None, '', 'NA') else None
            events.append((ids.record(), 0 if new_psn else 1, dt_sql(r[base]), new_inv, new_psn, remark))
        tv_rows.append(dict(row=n, serial=serial, prod=prod, cust=cust, inv=inv, psn=psn, ipsn=ids.record(),
                            psnw=psnw, start=dt_sql(r[0]), period=period, ext=ext, events=events))

    # ── SQL ───────────────────────────────────────────────────────────────────────────────────────────────────────
    def guarded(table, id_col, cols, values):
        sql.append('INSERT INTO `%s` (%s) SELECT %s FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `%s` WHERE `%s` = %s);'
                   % (table, ', '.join('`%s`' % c for c in cols), ', '.join(sql_str(v) for v in values),
                      table, id_col, sql_str(values[0])))
        undo[table].append(values[0])

    for sku, pid in new_products.items():
        guarded('products', 'product_id',
                ['product_id', 'sku', 'gtin', 'title', 'name', 'description', 'picture', 'weight', 'height', 'width',
                 'depth', 'price', 'tax_included', 'physical_product', 'has_sn'],
                [pid, sku, '', NEW_PRODUCTS[sku], NEW_PRODUCTS[sku], '', '', '', '', '', '', '', '1', '1', '1'])
    for pid in SWITCH_ON_HAS_SN:
        sql.append("UPDATE `products` SET `has_sn` = 1 WHERE `product_id` = %s AND `has_sn` = 0;" % sql_str(pid))
    for cid, name in new_customers.values():
        guarded('customers', 'customer_id',
                ['customer_id', 'customer_source', 'user_id', 'title', 'company_name', 'first_name', 'middle_name',
                 'last_name', 'primary_email', 'alternate_email', 'created_at', 'updated_at', 'state', 'primary_mobile',
                 'alternate_mobile', 'currency'],
                [cid, CUSTOMER_SOURCE_DIRECT, '', '', name, '', '', '', '', '', stamp, '', '', '', '', ''])
    for no, (iid, date, cid) in new_invoices.items():
        guarded('order_invoices', 'invoice_id', ['invoice_id', 'invoice_no', 'invoice_date', 'customer_id'], [iid, no, date, cid])
    for psn, prod, serial, _, _ in created_tvs:
        guarded('product_sn', 'product_sn_id', ['product_sn_id', 'product_id', 'serial_number', 'created_on', 'allotted_to_customer'],
                [psn, prod, serial, stamp, '1'])
    for psn, _, _, _ in reused:
        sql.append("UPDATE `product_sn` SET `allotted_to_customer` = 1 WHERE `product_sn_id` = %s AND `allotted_to_customer` = 0;" % sql_str(psn))
    for t in tv_rows:
        guarded('invoice_product_sn', 'ipsn_id', ['ipsn_id', 'invoice_id', 'product_sn_id'], [t['ipsn'], t['inv'], t['psn']])
        guarded('product_sn_warranty', 'psnw_id', ['psnw_id', 'product_sn_id', 'warranty_start_date', 'warranty_period', 'remarks'],
                [t['psnw'], t['psn'], t['start'], str(t['period']), WARRANTY_REMARK])
        if t['ext']:
            guarded('product_sn_extended_warranty', 'psnexw_id',
                    ['psnexw_id', 'psnw_id', 'extended_warranty_start_date', 'extended_warranty_period', 'remarks'],
                    [t['ext'][0], t['psnw'], t['ext'][1], str(t['ext'][2]), EXT_REMARK])
        for ev in t['events']:
            guarded('product_warranty_replacements', 'pwrp_id',
                    ['pwrp_id', 'is_repairable', 'psnw_id', 'date_of_service', 'new_invoice_id', 'new_product_sn_id', 'remarks'],
                    [ev[0], str(ev[1]), t['psnw'], ev[2], ev[3], ev[4], ev[5]])

    header = ['-- %s: warranty import from "%s" (sheet "%s")' % (args.name, args.excel.replace('\\', '/').split('/')[-1], args.sheet),
              '-- Generated by tools/warranty_import/warranty_import.py on %s (SGT) from snapshot of %s.' % (stamp, snap['database']),
              '-- All or nothing: one transaction; every INSERT is guarded by NOT EXISTS on its ID, so a second run changes nothing.',
              '-- Undo: migrations/undo/%s_undo.sql' % args.name, '', 'START TRANSACTION;', '']
    with open('%s/%s.sql' % (args.migrations, args.name), 'w', encoding='utf-8', newline='\n') as fh:
        fh.write('\n'.join(header + sql + ['', 'COMMIT;', '']))

    u = ['-- UNDO of %s: removes exactly what that migration added (run only if the import must be taken back).' % args.name,
         '-- To run it with the deploy script, move it into migrations/ and commit it.', '', 'START TRANSACTION;', '']
    for table, col in [('product_warranty_replacements', 'pwrp_id'), ('product_sn_extended_warranty', 'psnexw_id'),
                       ('product_sn_warranty', 'psnw_id'), ('invoice_product_sn', 'ipsn_id'), ('product_sn', 'product_sn_id'),
                       ('order_invoices', 'invoice_id'), ('customers', 'customer_id'), ('products', 'product_id')]:
        vals = undo.get(table, [])
        for i in range(0, len(vals), 500):
            u.append('DELETE FROM `%s` WHERE `%s` IN (%s);' % (table, col, ', '.join(sql_str(v) for v in vals[i:i + 500])))
    if reused:
        u.append('UPDATE `product_sn` SET `allotted_to_customer` = 0 WHERE `product_sn_id` IN (%s);' % ', '.join(sql_str(p[0]) for p in reused))
    for pid in SWITCH_ON_HAS_SN:
        if str(products[pid]['has_sn']) == '0':
            u.append("UPDATE `products` SET `has_sn` = 0 WHERE `product_id` = %s;" % sql_str(pid))
    with open('%s/undo/%s_undo.sql' % (args.migrations, args.name), 'w', encoding='utf-8', newline='\n') as fh:
        fh.write('\n'.join(u + ['', 'COMMIT;', '']))

    # ── Report ────────────────────────────────────────────────────────────────────────────────────────────────────
    L = []
    L.append('DRY-RUN REPORT  %s  (snapshot: %s, %s SGT)' % (args.name, snap['database'], stamp))
    L.append('Sheet "%s": %d data rows = %d TVs sold' % (args.sheet, len(rows), len(tv_rows)))
    L.append('')
    L.append('New products (%d):' % len(new_products))
    for sku, pid in new_products.items():
        L.append('  %s  SKU %s  %s' % (pid, sku, NEW_PRODUCTS[sku]))
    for pid in SWITCH_ON_HAS_SN:
        L.append('  has_sn switched on: %s %s' % (pid, products[pid]['name']))
    L.append('New customers (%d):' % len(new_customers))
    for cid, name in new_customers.values():
        L.append('  %s  %s' % (cid, name))
    used_existing = sorted({t['cust'] for t in tv_rows} - {c[0] for c in new_customers.values()})
    names = {c['customer_id']: c['company_name'] for c in snap['customers']}
    L.append('Existing customers used (%d): %s' % (len(used_existing), ', '.join(names[c] for c in used_existing)))
    L.append('')
    L.append('Invoices (%d new):' % len(new_invoices))
    per_inv = collections.Counter(t['inv'] for t in tv_rows)
    all_names = dict(names); all_names.update({c[0]: c[1] for c in new_customers.values()})
    for no, (iid, date, cid) in sorted(new_invoices.items()):
        L.append('  %-6s %s  %-28s %4d TVs%s' % (no, date[:10], all_names[cid], per_inv.get(iid, 0),
                                                '  (service invoice)' if per_inv.get(iid, 0) == 0 else ''))
    L.append('')
    by_prod = collections.Counter(t['prod'] for t in tv_rows)
    pname = {p['product_id']: p['name'] for p in snap['products']}
    pname.update({pid: NEW_PRODUCTS[sku] for sku, pid in new_products.items()})
    L.append('TVs sold by product:')
    for pid, c in by_prod.most_common():
        L.append('  %4d  %s' % (c, pname[pid]))
    sold_reused = sum(1 for p in reused if p[3] == 'sold')
    L.append('TV records: %d reused from stock, %d new (incl. %d replacement TVs)'
             % (len(reused), len(created_tvs), sum(1 for c in created_tvs if c[4] == 'replacement')))
    L.append('Warranties: %d   Extended warranties: %d   Service events: %d (%d replacements, %d repairs)'
             % (len(tv_rows), sum(1 for t in tv_rows if t['ext']),
                sum(len(t['events']) for t in tv_rows),
                sum(1 for t in tv_rows for e in t['events'] if e[4]), sum(1 for t in tv_rows for e in t['events'] if not e[4])))
    periods = collections.Counter(t['period'] for t in tv_rows)
    L.append('Warranty periods (days): %s' % dict(periods))
    L.append('')
    L.append('Serials already in the database that get a NEW TV record (not free stock of the same product):')
    existing_serials = collections.defaultdict(list)
    for ps in snap['product_sn']:
        existing_serials[ps['serial_number'].strip().upper()].append(ps)
    for psn, prod, serial, row_no, why in created_tvs:
        if serial in existing_serials:
            ex = existing_serials[serial]
            L.append('  row %-5s %-15s %-11s existing: %s' % (row_no, serial, why, '; '.join(
                '%s %s' % (pname.get(e['product_id'], e['product_id']),
                           'invoiced' if str(e['invoiced']) == '1' else 'order' if str(e['in_order']) == '1'
                           else 'replacement' if str(e['is_replacement']) == '1' else 'allotted' if str(e['allotted_to_customer']) == '1'
                           else 'stock') for e in ex)))
    dup_in_sheet = [s for s, c in collections.Counter(t['serial'] for t in tv_rows).items() if c > 1]
    L.append('Serials twice in the sheet (each row = its own TV): %s' % (', '.join(dup_in_sheet) or 'none'))
    L.append('')
    L.append('Service events:')
    for t in tv_rows:
        for ev in t['events']:
            L.append('  row %-5s %-15s %s  %-11s new TV %-15s ref %-6s %s' % (
                t['row'], t['serial'], ev[2][:10], 'replacement' if ev[4] else 'repair',
                next((c[2] for c in created_tvs if c[0] == ev[4]), next((p[1] for p in reused if p[0] == ev[4]), '-')),
                next((no for no, v in new_invoices.items() if v[0] == ev[3]),
                     next((no for no, v in invoice_by_no.items() if v['invoice_id'] == ev[3]), '-')), ev[5]))
    L.append('')
    L.append('SQL statements: %d  ->  %s/%s.sql' % (len(sql), args.migrations, args.name))
    with open(args.report, 'w', encoding='utf-8') as fh:
        fh.write('\n'.join(L) + '\n')
    print('\n'.join(L))


if __name__ == '__main__':
    main()
