
// 1. Allow only lowercase alphabets, upper case alphabets and digits 0 to 9
function isValidUsername( str, isSpaceAllowed ){
	var pattern = "";
	if( isSpaceAllowed )
		pattern = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789 ";
	else
		pattern = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
	
	if( str.length == 0 )
		return false;
	for( var i = 0; i < str.length ; i++ ){
		if( pattern.indexOf( str.charAt( i ) ) == -1 ){
			console.log( str.charAt( i ) );
			return false;
		}
	}
	return true;
}

// 2. Validate URL
function isValidURL( url, is_with_http ){
	// URL should not contain a white space
	if( is_with_http ){ url = "";
		if( url.indexOf( "http://", 0 ) == -1 )
			return false;
	} 
	if( url.indexOf( "http://", 0 ) != -1 )
		return false;
	
	return true;
}

// 3. Validate Mac Address
function isValidMac( mac ){
	/*  Validate the Fields
		1. Mac should be of 17 characters long
		2. ":" should be on the positions 2, 5, 8, 11, 14
		3. Total number of ":" should be 5
		4. Characters range from 0 to 9, a to f, A to F
	 */
	var pattern = "abcdefABCDEF0123456789:";
	if( mac.length < 17 )
		return false;
	//console.log( "Len : "+mac.length );
	
	if( ( mac.charAt( 2 ) != ':' ) || 
			( mac.charAt( 5 ) != ':' ) ||
			( mac.charAt( 8 ) != ':' ) ||
			( mac.charAt( 11 ) != ':' ) ||
			( mac.charAt( 14 ) != ':' ) ){
		return false;
	}
	//console.log( countChars( mac, ':' ) );
	if( countChars( mac, ':' ) != 5 )
		return false;
	
	for( var i = 0 ; i < 17 ; i++ ){
		if( pattern.indexOf( mac.charAt( i ) ) == -1 )
			return false;
	}
	return true;
}

// 4. Check the validity of any expression and provide the possible characters in the pattern
function isValidCombination( str, pattern ){
	for( var i = 0 ; i < str.length ; i++ ){
		if( pattern.indexOf( str.charAt( i ) ) == -1 )
			return false;
	}
	return true;
}

// 5. Validate a password (for now, checking for only emptiness)
function isValidPassword( str ){
	if( str.trim().length == 0 )
		return false;
	return true;
}

// 6. Validate Email
function isValidEmail( str ){
	if( str.trim().length == 0 )
		return false;
	return true;
}