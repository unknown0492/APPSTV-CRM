import sys
from pyzbar.pyzbar import decode
from PIL import Image

if len( sys.argv ) < 2:
    print( "ERROR: No image path provided" )
    sys.exit( 1 )

image_path = sys.argv[ 1 ]

try:
    image   = Image.open( image_path )
    results = decode( image )

    if not results:
        print( "ERROR: No QR code or barcode detected" )
        sys.exit( 1 )

    # Print first detected result only
    print( results[ 0 ].data.decode( 'utf-8' ) )
    sys.exit( 0 )

except Exception as e:
    print( "ERROR: " + str( e ) )
    sys.exit( 1 )