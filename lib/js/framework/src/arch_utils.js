
// [LIB - Smart.Framework / JS / Arch Utils]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

// DEPENDS: smartJ$Utils, smartJ$BaseConv, smartJ$CryptoHash

//==================================================================
// The code is released under the BSD License.
//  Copyright (c) unix-world.org
// The file contains portions of code from:
//	SnappyJs: github.com/zhipeng-jia/snappyjs, Copyright (c) Zhipeng Jia, License: MIT
//==================================================================

//================== [ES6]


//=======================================
// CLASS :: Snappy (Internal) Compress
//=======================================

/**
 * CLASS :: Smart Snappy Internal Compress (ES6, Strict Mode)
 *
 * @package Sf.Javascript:Arch
 *
 * @private : for internal use only
 *
 * @requires		smartJ$Utils
 *
 * @desc The class provides the snappy internal compress methods for JavaScript API of Smart.Framework
 * @author unix-world.org
 * @license BSD
 * @file arch_utils.js
 * @version 20251216
 * @class smartJ$SnappyCompress
 * @static
 * @frozen
 *
 */
const smartJ$SnappyCompress = class{constructor(plainText) { // OBJECT-CLASS
	'use strict';
	const _N$ = 'smartJ$SnappyCompress';

	// -> dynamic (new)
	const _C$ = this; // self referencing

	const _p$ = console;

	const _Utils$ = smartJ$Utils;

	const BLOCK_LOG = 16;
	const BLOCK_SIZE = 1 << BLOCK_LOG;
	const MAX_HASH_TABLE_BITS = 14;
	const hashTables = new Array(MAX_HASH_TABLE_BITS + 1);

	const compress = () => {
		//--
		const arr = new Uint8Array(_Utils$.strToByteArray(plainText));
		//--
		const len = maxCompressedLength(arr);
		if(len <= 0) {
			_p$.warn(_N$, 'Max Compressed Length is Zero');
			return '';
		} //end if
		//--
		let outBuffer = new Uint8Array(len);
		const cLen = compressToBuffer(arr, outBuffer);
		if(cLen <= 0) {
			_p$.warn(_N$, 'Compress Failed');
			return '';
		} //end if
		outBuffer = outBuffer.slice(0, cLen);
		if(outBuffer.length <= 0) {
			_p$.warn(_N$, 'Compress Slice Failed');
			return '';
		} //end if
		//--
		return String.fromCharCode(...outBuffer);
		//--
	}; //END
	_C$.compress = compress; // export

	const compressToBuffer = (arr, outBuffer) => {
		//--
		let length = arr.length;
		let pos = 0;
		let outPos = 0;
		//--
		outPos = putVarint(length, outBuffer, outPos);
		//--
		let fragmentSize;
		while(pos < length) {
			fragmentSize = Math.min(length - pos, BLOCK_SIZE);
			outPos = compressFragment(arr, pos, fragmentSize, outBuffer, outPos);
			pos += fragmentSize;
		} //end while
		//--
		return outPos;
		//--
	}; //END
	// no export

	const maxCompressedLength = (arr) => {
		let sourceLen = arr.length;
		return 32 + sourceLen + Math.floor(sourceLen / 6);
	}; //END
	// no export

	const copyBytes = (fromArr, fromPos, toArr, toPos, len) => {
		//--
		for(let i=0; i<len; i++) {
			toArr[toPos + i] = fromArr[fromPos + i];
		} //end for
		//--
	}; //END
	// no export

	const hashFunc = (key, hashFuncShift) => {
		//--
		return (key * 0x1e35a7bd) >>> hashFuncShift;
		//--
	}; //END
	// no export

	const load32 = (theArr, pos) => {
		//--
		return theArr[pos] + (theArr[pos + 1] << 8) + (theArr[pos + 2] << 16) + (theArr[pos + 3] << 24);
		//--
	}; //END
	// no export

	const equals32 = (theArr, pos1, pos2) => {
		//--
		return theArr[pos1] === theArr[pos2] && theArr[pos1 + 1] === theArr[pos2 + 1] && theArr[pos1 + 2] === theArr[pos2 + 2] && theArr[pos1 + 3] === theArr[pos2 + 3];
		//--
	}; //END
	// no export

	const emitLiteral = (input, ip, len, output, op) => {
		//--
		if(len <= 60) {
			output[op] = (len - 1) << 2;
			op++;
		} else if(len < 256) {
			output[op] = 60 << 2;
			output[op + 1] = len - 1;
			op += 2;
		} else {
			output[op] = 61 << 2;
			output[op + 1] = (len - 1) & 0xff;
			output[op + 2] = (len - 1) >>> 8;
			op += 3;
		} //end if else
		//--
		copyBytes(input, ip, output, op, len);
		//--
		return op + len;
		//--
	}; //END
	// no export

	const emitCopyLessThan64 = (output, op, offset, len) => {
		//--
		if(len < 12 && offset < 2048) {
			//--
			output[op] = 1 + ((len - 4) << 2) + ((offset >>> 8) << 5);
			output[op + 1] = offset & 0xff;
			//--
			return op + 2;
			//--
		} //end if
		//--
		output[op] = 2 + ((len - 1) << 2);
		output[op + 1] = offset & 0xff;
		output[op + 2] = offset >>> 8;
		//--
		return op + 3;
		//--
	}; //END
	// no export

	const emitCopy = (output, op, offset, len) => {
		//--
		while(len >= 68) {
			op = emitCopyLessThan64(output, op, offset, 64);
			len -= 64;
		} //end while
		//--
		if(len > 64) {
			op = emitCopyLessThan64(output, op, offset, 60);
			len -= 60;
		} //end if
		//--
		return emitCopyLessThan64(output, op, offset, len);
		//--
	}; //END
	// no export

	const putVarint = (value, output, op) => {
		//--
		do {
			output[op] = value & 0x7f;
			value = value >>> 7;
			if(value > 0) {
				output[op] += 0x80;
			} //end if
			op++;
		} while(value > 0);
		//--
		return op;
		//--
	}; //END
	// no export

	const compressFragment = (input, ip, inputSize, output, op) => {
		//--
		let hashTableBits = 1;
		while(((1 << hashTableBits) <= inputSize) && (hashTableBits <= MAX_HASH_TABLE_BITS)) {
			hashTableBits += 1;
		} //end while
		hashTableBits -= 1;
		//--
		let hashFuncShift = 32 - hashTableBits;
		if(typeof(hashTables[hashTableBits]) === 'undefined') {
			hashTables[hashTableBits] = new Uint16Array(1 << hashTableBits);
		} //end if
		//--
		let hashTable = hashTables[hashTableBits];
		for(let i=0; i<hashTable.length; i++) {
			hashTable[i] = 0;
		} //end for
		//--
		let ipEnd = ip + inputSize;
		let baseIp = ip;
		let nextEmit = ip;
		//--
		let hash, nextHash;
		let nextIp, candidate, skip;
		let bytesBetweenHashLookups;
		let base, matched, offset;
		let prevHash, curHash;
		let ipLimit;
		//--
		let INPUT_MARGIN = 15;
		if(inputSize >= INPUT_MARGIN) {
			ipLimit = ipEnd - INPUT_MARGIN;
			ip++;
			nextHash = hashFunc(load32(input, ip), hashFuncShift);
			let flag = true;
			while(flag) {
				//--
				skip = 32;
				nextIp = ip;
				do {
					ip = nextIp;
					hash = nextHash;
					bytesBetweenHashLookups = skip >>> 5;
					skip++;
					nextIp = ip + bytesBetweenHashLookups;
					if(ip > ipLimit) {
						flag = false;
						break;
					} //end if
					nextHash = hashFunc(load32(input, nextIp), hashFuncShift);
					candidate = baseIp + hashTable[hash];
					hashTable[hash] = ip - baseIp;
				} while(!equals32(input, ip, candidate));
				//--
				if(!flag) {
					break;
				} //end if
				//--
				op = emitLiteral(input, nextEmit, ip - nextEmit, output, op);
				//--
				do {
					base = ip;
					matched = 4;
					while(((ip + matched) < ipEnd) && (input[ip + matched] === input[candidate + matched])) {
						matched++;
					} //end while
					ip += matched;
					offset = base - candidate;
					op = emitCopy(output, op, offset, matched);
					nextEmit = ip;
					if(ip >= ipLimit) {
						flag = false;
						break;
					} //end if
					prevHash = hashFunc(load32(input, ip - 1), hashFuncShift);
					hashTable[prevHash] = ip - 1 - baseIp;
					curHash = hashFunc(load32(input, ip), hashFuncShift);
					candidate = baseIp + hashTable[curHash];
					hashTable[curHash] = ip - baseIp;
				} while(equals32(input, ip, candidate));
				//--
				if(!flag) {
					break;
				} //end if
				//--
				ip++;
				nextHash = hashFunc(load32(input, ip), hashFuncShift);
				//--
			} //end while
			//--
		} //end if
		//--
		if(nextEmit < ipEnd) {
			op = emitLiteral(input, nextEmit, ipEnd - nextEmit, output, op);
		} //end if
		//--
		return op;
		//--
	}; //END
	// no export

}}; //END OBJECT-CLASS

Object.freeze(smartJ$SnappyCompress); // this must be cloned with new, thus the new object will not be frozen !

if(typeof(window) != 'undefined') {
	window.smartJ$SnappyCompress = smartJ$SnappyCompress; // global export
} //end if


//=======================================
// CLASS :: Snappy (Internal) Uncompress
//=======================================

/**
 * CLASS :: Smart Snappy Internal Uncompress (ES6, Strict Mode)
 *
 * @package Sf.Javascript:Arch
 *
 * @private : for internal use only
 *
 * @requires		smartJ$Utils
 *
 * @desc The class provides the snappy internal uncompress methods for JavaScript API of Smart.Framework
 * @author unix-world.org
 * @license BSD
 * @file arch_utils.js
 * @version 20251216
 * @class smartJ$SnappyUncompress
 * @static
 * @frozen
 *
 */
const smartJ$SnappyUncompress = class{constructor(compressedText) { // OBJECT-CLASS
	'use strict';
	const _N$ = 'smartJ$SnappyUncompress';

	// -> dynamic (new)
	const _C$ = this; // self referencing

	const _p$ = console;

	const _Utils$ = smartJ$Utils;

	const WORD_MASK = [ 0, 0xff, 0xffff, 0xffffff, 0xffffffff ];
	let pos = 0;

	const uncompress = () => {
		//--
		const arr = new Uint8Array(_Utils$.strToByteArray(compressedText));
		//--
		const len = readUncompressedLength(arr);
		if(len <= 0) {
			_p$.warn(_N$, 'Uncompressed Length is Zero');
			return '';
		} //end if
		//--
		const outBuffer = new Uint8Array(len);
		if(uncompressToBuffer(arr, outBuffer) !== true) {
			_p$.warn(_N$, 'Uncompress Failed');
			return '';
		} //end if
		//--
		return String.fromCharCode(...outBuffer);
		//--
	}; //END
	_C$.uncompress = uncompress; // export

	const readUncompressedLength = (arr) => {
		//--
		let result = 0;
		let shift = 0;
		//--
		let c, val;
		while(shift < 32 && pos < arr.length) {
			c = arr[pos];
			pos++;
			val = c & 0x7f;
			if(((val << shift) >>> shift) !== val) {
				return -1;
			} //end if
			result |= val << shift;
			if(c < 128) {
				return result;
			} //end if
			shift += 7;
		} //end while
		//--
		return -1;
		//--
	}; //END
	// no export

	const uncompressToBuffer = (arr, outBuffer) => {
		//--
		const arrLen = arr.length;
		//--
		let p0s = pos;
		let outPos = 0;
		//--
		let c, len, smallLen, offset;
		while(p0s < arr.length) {
			c = arr[p0s];
			p0s++;
			if((c & 0x3) === 0) {
				len = (c >>> 2) + 1; // literal
				if(len > 60) {
					if((p0s + 3) >= arrLen) {
						return false;
					} //end if
					smallLen = len - 60;
					len = arr[p0s] + (arr[p0s + 1] << 8) + (arr[p0s + 2] << 16) + (arr[p0s + 3] << 24);
					len = (len & WORD_MASK[smallLen]) + 1;
					p0s += smallLen;
				} //end if
				if((p0s + len) > arrLen) {
					return false;
				} //end if
				copyBytes(arr, p0s, outBuffer, outPos, len);
				p0s += len;
				outPos += len;
			} else {
				switch(c & 0x3) {
					case 1:
						len = ((c >>> 2) & 0x7) + 4;
						offset = arr[p0s] + ((c >>> 5) << 8);
						p0s++;
						break;
					case 2:
						if((p0s + 1) >= arrLen) {
							return false;
						} //end if
						len = (c >>> 2) + 1;
						offset = arr[p0s] + (arr[p0s + 1] << 8);
						p0s += 2;
						break;
					case 3:
						if((p0s + 3) >= arrLen) {
							return false;
						} //end if
						len = (c >>> 2) + 1;
						offset = arr[p0s] + (arr[p0s + 1] << 8) + (arr[p0s + 2] << 16) + (arr[p0s + 3] << 24);
						p0s += 4;
						break;
					default:
						// n/a
				} //end switch
				if((offset === 0) || (offset > outPos)) {
					return false;
				} //end if
				selfCopyBytes(outBuffer, outPos, offset, len);
				outPos += len;
			} //end if else
		} //end while
		//--
		return true;
		//--
	}; //END
	// no export

	const copyBytes = (fromArr, fromPos, toArr, toPos, len) => {
		//--
		for(let i=0; i<len; i++) {
			toArr[toPos + i] = fromArr[fromPos + i];
		} //end for
		//--
	}; //END
	// no export

	const selfCopyBytes = (theArr, pos, offset, len) => {
		for(let i=0; i<len; i++) {
			theArr[pos + i] = theArr[pos - offset + i];
		} //end for
	}; //END
	// no export

}}; //END OBJECT-CLASS

Object.freeze(smartJ$SnappyUncompress); // this must be cloned with new, thus the new object will not be frozen !

if(typeof(window) != 'undefined') {
	window.smartJ$SnappyUncompress = smartJ$SnappyUncompress; // global export
} //end if


//=======================================
// CLASS :: Arch Snappy
//=======================================

/**
 * CLASS :: Smart Arch Snappy (ES6, Strict Mode)
 *
 * @package Sf.Javascript:Arch
 *
 * @requires		smartJ$Utils
 * @requires		smartJ$BaseConv
 * @requires		smartJ$CryptoHash
 * @requires		smartJ$SnappyCompress
 * @requires		smartJ$SnappyUncompress
 *
 * @desc The class provides the snappy compress/uncompress methods for JavaScript API of Smart.Framework
 * @author unix-world.org
 * @license BSD
 * @file arch_utils.js
 * @version 20251216
 * @class smartJ$ArchSnappy
 * @static
 * @frozen
 *
 */
const smartJ$ArchSnappy = new class{constructor(){ // STATIC CLASS (ES6)
	'use strict';
	const _N$ = 'smartJ$ArchSnappy';

	// :: static
	const _C$ = this; // self referencing

	const _p$ = console;

	let SECURED = false;
	_C$.secureClass = () => { // implements class security
		if(SECURED === true) {
			_p$.warn(_N$, 'Class is already SECURED');
		} else {
			SECURED = true;
			Object.freeze(_C$);
		} //end if
	}; //END

	const _Utils$ = smartJ$Utils;
	const _Ba$eConv = smartJ$BaseConv;
	const _Crypto$Hash = smartJ$CryptoHash;

	const pfx = 'sy1!';

	const compress = (txtPlain) => {
		//--
		const _m$ = 'compress';
		//--
		txtPlain = _Utils$.stringPureVal(txtPlain); // don't trim
		if(txtPlain == '') {
			return '';
		} //end if
		//--
		const crc = _Crypto$Hash.crc32b(txtPlain, true);
		const snapC = new smartJ$SnappyCompress(txtPlain);
		txtPlain = null; // free mem
		//--
		let b64Arch = String(snapC.compress() || '');
		if(b64Arch == '') {
			_p$.warn(_N$, _m$, 'Arch is Empty');
			return '';
		} //end if
		//--
		b64Arch = _Utils$.stringTrim(_Ba$eConv.b64s_enc(b64Arch, true, false)); // binary, b64u
		if(b64Arch == '') {
			_p$.warn(_N$, _m$, 'B64 Data is Empty');
			return '';
		} //end if
		//--
		return String(pfx + ';' + b64Arch + ';' + crc);
		//--
	}; //END
	_C$.compress = compress; // export

	const uncompress = (b64Arch) => {
		//--
		const _m$ = 'uncompress';
		//--
		b64Arch = _Utils$.stringPureVal(b64Arch, true); // trim, b64
		if(b64Arch == '') {
			return '';
		} //end if
		//--
		let parts = b64Arch.split(';', 3);
		if(parts[0] !== pfx) {
			_p$.warn(_N$, _m$, 'Invalid Signature');
			return '';
		} //end if
		const crc = _Utils$.stringPureVal(parts[2], true);
		b64Arch = _Utils$.stringPureVal(parts[1], true);
		parts = null; // free mem
		//--
		if(crc == '') {
			_p$.warn(_N$, _m$, 'Empty CRC');
			return '';
		} //end if
		if(b64Arch == '') {
			_p$.warn(_N$, _m$, 'Empty B64 Data');
			return '';
		} //end if
		//--
		b64Arch = _Ba$eConv.b64s_dec(b64Arch, true); // binary
		if(b64Arch == '') {
			_p$.warn(_N$, _m$, 'Empty Arch Data');
			return '';
		} //end if
		//--
		const snapU = new smartJ$SnappyUncompress(b64Arch);
		b64Arch = null; // free mem
		//--
		const txtPlain = String(snapU.uncompress() || '');
		if(txtPlain == '') {
			_p$.warn(_N$, _m$, 'Empty Data');
			return '';
		} //end if
		//--
		if(_Crypto$Hash.crc32b(txtPlain, true) !== crc) {
			_p$.warn(_N$, _m$, 'Data CRC Failed');
			return '';
		} //end if
		//--
		return String(txtPlain);
		//--
	}; //END
	_C$.uncompress = uncompress; // export

}}; //END CLASS

smartJ$ArchSnappy.secureClass(); // implements class security

if(typeof(window) != 'undefined') {
	window.smartJ$ArchSnappy = smartJ$ArchSnappy; // global export
} //end if

//==================================================================
//==================================================================

// #END
