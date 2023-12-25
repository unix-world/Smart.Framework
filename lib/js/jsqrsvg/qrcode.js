
//--
// QRCode for JavaScript
// (c) 2023 unix-world.org
// License: BSD
// modified, adapted, optimized by unixman: r.20231205
//
// transpiled by unixman to ES6 by unixman via: lebab.github.io
//
// ORIGINAL PROJECT:
// Copyright (c) 2009 Kazuhiko Arase
// https://github.com/datalog/qrcode-svg/qrcode.js
// # (2020.02.20)
// License: MIT
//--


const smartQRCode = new class{constructor(){ // STATIC CLASS
	'use strict';
	const _N$ = 'smartQRCode';

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

	//--
	const QRMode = {
		MODE_NUMBER: 1 << 0,
		MODE_ALPHA_NUM: 1 << 1,
		MODE_8BIT_BYTE: 1 << 2,
		MODE_KANJI: 1 << 3
	};
	//--
	const QRErrorCorrectLevel = {
		L: 1,
		M: 0,
		Q: 3,
		H: 2
	};
	//--
	const QRMaskPattern = {
		PATTERN000: 0,
		PATTERN001: 1,
		PATTERN010: 2,
		PATTERN011: 3,
		PATTERN100: 4,
		PATTERN101: 5,
		PATTERN110: 6,
		PATTERN111: 7
	};
	//--
	const QRUtil = {
		PATTERN_POSITION_TABLE: [
			[],
			[6, 18],
			[6, 22],
			[6, 26],
			[6, 30],
			[6, 34],
			[6, 22, 38],
			[6, 24, 42],
			[6, 26, 46],
			[6, 28, 50],
			[6, 30, 54],
			[6, 32, 58],
			[6, 34, 62],
			[6, 26, 46, 66],
			[6, 26, 48, 70],
			[6, 26, 50, 74],
			[6, 30, 54, 78],
			[6, 30, 56, 82],
			[6, 30, 58, 86],
			[6, 34, 62, 90],
			[6, 28, 50, 72, 94],
			[6, 26, 50, 74, 98],
			[6, 30, 54, 78, 102],
			[6, 28, 54, 80, 106],
			[6, 32, 58, 84, 110],
			[6, 30, 58, 86, 114],
			[6, 34, 62, 90, 118],
			[6, 26, 50, 74, 98, 122],
			[6, 30, 54, 78, 102, 126],
			[6, 26, 52, 78, 104, 130],
			[6, 30, 56, 82, 108, 134],
			[6, 34, 60, 86, 112, 138],
			[6, 30, 58, 86, 114, 142],
			[6, 34, 62, 90, 118, 146],
			[6, 30, 54, 78, 102, 126, 150],
			[6, 24, 50, 76, 102, 128, 154],
			[6, 28, 54, 80, 106, 132, 158],
			[6, 32, 58, 84, 110, 136, 162],
			[6, 26, 54, 82, 110, 138, 166],
			[6, 30, 58, 86, 114, 142, 170]
		],
		G15: (1 << 10) | (1 << 8) | (1 << 5) | (1 << 4) | (1 << 2) | (1 << 1) | (1 << 0),
		G18: (1 << 12) | (1 << 11) | (1 << 10) | (1 << 9) | (1 << 8) | (1 << 5) | (1 << 2) | (1 << 0),
		G15_MASK: (1 << 14) | (1 << 12) | (1 << 10) | (1 << 4) | (1 << 1),
		getBCHTypeInfo(data) {
			let d = data << 10;
			while (QRUtil.getBCHDigit(d) - QRUtil.getBCHDigit(QRUtil.G15) >= 0) {
				d ^= (QRUtil.G15 << (QRUtil.getBCHDigit(d) - QRUtil.getBCHDigit(QRUtil.G15)));
			}
			return ((data << 10) | d) ^ QRUtil.G15_MASK;
		},
		getBCHTypeNumber(data) {
			let d = data << 12;
			while (QRUtil.getBCHDigit(d) - QRUtil.getBCHDigit(QRUtil.G18) >= 0) {
				d ^= (QRUtil.G18 << (QRUtil.getBCHDigit(d) - QRUtil.getBCHDigit(QRUtil.G18)));
			}
			return (data << 12) | d;
		},
		getBCHDigit(data) {
			let digit = 0;
			while (data != 0) {
				digit++;
				data >>>= 1;
			}
			return digit;
		},
		getPatternPosition(typeNumber) {
			return QRUtil.PATTERN_POSITION_TABLE[typeNumber - 1];
		},
		getMask(maskPattern, i, j) {
			switch (maskPattern) {
				case QRMaskPattern.PATTERN000:
					return (i + j) % 2 == 0;
				case QRMaskPattern.PATTERN001:
					return i % 2 == 0;
				case QRMaskPattern.PATTERN010:
					return j % 3 == 0;
				case QRMaskPattern.PATTERN011:
					return (i + j) % 3 == 0;
				case QRMaskPattern.PATTERN100:
					return (Math.floor(i / 2) + Math.floor(j / 3)) % 2 == 0;
				case QRMaskPattern.PATTERN101:
					return (i * j) % 2 + (i * j) % 3 == 0;
				case QRMaskPattern.PATTERN110:
					return ((i * j) % 2 + (i * j) % 3) % 2 == 0;
				case QRMaskPattern.PATTERN111:
					return ((i * j) % 3 + (i + j) % 2) % 2 == 0;
				default:
					throw new Error(`bad maskPattern:${maskPattern}`);
					return false;
			}
		},
		getErrorCorrectPolynomial(errorCorrectLength) {
			let a = new QRPolynomial([1], 0);
			for (let i = 0; i < errorCorrectLength; i++) {
				a = a.multiply(new QRPolynomial([1, QRMath.gexp(i)], 0));
			}
			return a;
		},
		getLengthInBits(mode, type) {
			if (1 <= type && type < 10) {
				switch (mode) {
					case QRMode.MODE_NUMBER:
						return 10;
					case QRMode.MODE_ALPHA_NUM:
						return 9;
					case QRMode.MODE_8BIT_BYTE:
						return 8;
					case QRMode.MODE_KANJI:
						return 8;
					default:
						throw new Error(`mode:${mode}`);
						return 0;
				}
			} else if (type < 27) {
				switch (mode) {
					case QRMode.MODE_NUMBER:
						return 12;
					case QRMode.MODE_ALPHA_NUM:
						return 11;
					case QRMode.MODE_8BIT_BYTE:
						return 16;
					case QRMode.MODE_KANJI:
						return 10;
					default:
						throw new Error(`mode:${mode}`);
						return 0;
				}
			} else if (type < 41) {
				switch (mode) {
					case QRMode.MODE_NUMBER:
						return 14;
					case QRMode.MODE_ALPHA_NUM:
						return 13;
					case QRMode.MODE_8BIT_BYTE:
						return 16;
					case QRMode.MODE_KANJI:
						return 12;
					default:
						throw new Error(`mode:${mode}`);
						return 0;
				}
			} else {
				throw new Error(`type:${type}`);
				return 0;
			}
		},
		getLostPoint(qrCode) {
			const moduleCount = qrCode.getModuleCount();
			let lostPoint = 0;
			for (let row = 0; row < moduleCount; row++) {
				for (let col = 0; col < moduleCount; col++) {
					let sameCount = 0;
					const dark = qrCode.isDark(row, col);
					for (let r = -1; r <= 1; r++) {
						if (row + r < 0 || moduleCount <= row + r) {
							continue;
						}
						for (let c = -1; c <= 1; c++) {
							if (col + c < 0 || moduleCount <= col + c) {
								continue;
							}
							if (r == 0 && c == 0) {
								continue;
							}
							if (dark == qrCode.isDark(row + r, col + c)) {
								sameCount++;
							}
						}
					}
					if (sameCount > 5) {
						lostPoint += (3 + sameCount - 5);
					}
				}
			}
			for (let row = 0; row < moduleCount - 1; row++) {
				for (let col = 0; col < moduleCount - 1; col++) {
					let count = 0;
					if (qrCode.isDark(row, col)) count++;
					if (qrCode.isDark(row + 1, col)) count++;
					if (qrCode.isDark(row, col + 1)) count++;
					if (qrCode.isDark(row + 1, col + 1)) count++;
					if (count == 0 || count == 4) {
						lostPoint += 3;
					}
				}
			}
			for (let row = 0; row < moduleCount; row++) {
				for (let col = 0; col < moduleCount - 6; col++) {
					if (qrCode.isDark(row, col) && !qrCode.isDark(row, col + 1) && qrCode.isDark(row, col + 2) && qrCode.isDark(row, col + 3) && qrCode.isDark(row, col + 4) && !qrCode.isDark(row, col + 5) && qrCode.isDark(row, col + 6)) {
						lostPoint += 40;
					}
				}
			}
			for (let col = 0; col < moduleCount; col++) {
				for (let row = 0; row < moduleCount - 6; row++) {
					if (qrCode.isDark(row, col) && !qrCode.isDark(row + 1, col) && qrCode.isDark(row + 2, col) && qrCode.isDark(row + 3, col) && qrCode.isDark(row + 4, col) && !qrCode.isDark(row + 5, col) && qrCode.isDark(row + 6, col)) {
						lostPoint += 40;
					}
				}
			}
			let darkCount = 0;
			for (let col = 0; col < moduleCount; col++) {
				for (let row = 0; row < moduleCount; row++) {
					if (qrCode.isDark(row, col)) {
						darkCount++;
					}
				}
			}
			const ratio = Math.abs(100 * darkCount / moduleCount / moduleCount - 50) / 5;
			lostPoint += ratio * 10;
			return lostPoint;
		}
	};
	//--
	const QRMath = {
		glog(n) {
			if (n < 1) {
				throw new Error(`glog(${n})`);
				return null;
			}
			return QRMath.LOG_TABLE[n];
		},
		gexp(n) {
			while (n < 0) {
				n += 255;
			}
			while (n >= 256) {
				n -= 255;
			}
			return QRMath.EXP_TABLE[n];
		},
		EXP_TABLE: new Array(256),
		LOG_TABLE: new Array(256)
	};
	//--
	for (let i = 0; i < 8; i++) {
		QRMath.EXP_TABLE[i] = 1 << i;
	}
	for (let i = 8; i < 256; i++) {
		QRMath.EXP_TABLE[i] = QRMath.EXP_TABLE[i - 4] ^ QRMath.EXP_TABLE[i - 5] ^ QRMath.EXP_TABLE[i - 6] ^ QRMath.EXP_TABLE[i - 8];
	}
	for (let i = 0; i < 255; i++) {
		QRMath.LOG_TABLE[QRMath.EXP_TABLE[i]] = i;
	}
	//--

	//--
	const QR8bitByte = class{constructor(data){
		const _cls = this;

		const mode = QRMode.MODE_8BIT_BYTE; _cls.mode = mode; // export

		let parsedData = [];

		// Added to support UTF-8 Characters
		for (let i = 0, l = data.length; i < l; i++) {
			const byteArray = [];
			const code = data.charCodeAt(i);
			if (code > 0x10000) {
				byteArray[0] = 0xF0 | ((code & 0x1C0000) >>> 18);
				byteArray[1] = 0x80 | ((code & 0x3F000) >>> 12);
				byteArray[2] = 0x80 | ((code & 0xFC0) >>> 6);
				byteArray[3] = 0x80 | (code & 0x3F);
			} else if (code > 0x800) {
				byteArray[0] = 0xE0 | ((code & 0xF000) >>> 12);
				byteArray[1] = 0x80 | ((code & 0xFC0) >>> 6);
				byteArray[2] = 0x80 | (code & 0x3F);
			} else if (code > 0x80) {
				byteArray[0] = 0xC0 | ((code & 0x7C0) >>> 6);
				byteArray[1] = 0x80 | (code & 0x3F);
			} else {
				byteArray[0] = code;
			}
			parsedData.push(byteArray);
		}
		parsedData = Array.prototype.concat.apply([], parsedData);
		if(parsedData.length != data.length) {
			parsedData.unshift(191);
			parsedData.unshift(187);
			parsedData.unshift(239);
		}

		const getLength = function(buffer) {
			return parsedData.length;
		};
		_cls.getLength = getLength; // export

		const write = function(buffer) {
			for (let i = 0, l = parsedData.length; i < l; i++) {
				buffer.put(parsedData[i], 8);
			}
		};
		_cls.write = write; // export

	}};
	//--

	//--
	const QRCodeModel = class{constructor(typeNumber, errorCorrectLevel){
		const _cls = this;

		const PAD0 = 0xEC; _cls.PAD0 = PAD0;
		const PAD1 = 0x11; _cls.PAD1 = PAD1;

		let modules = null;
		let moduleCount = 0;
		let dataCache = null;
		let dataList = [];

		const addData = function(data) {
			const newData = new QR8bitByte(data);
			dataList.push(newData);
			dataCache = null;
		};
		_cls.addData = addData; // export

		const isDark = (row, col) => {
			if (row < 0 || moduleCount <= row || col < 0 || moduleCount <= col) {
				throw new Error(`${row},${col}`);
				return null;
			}
			return modules[row][col];
		};
		_cls.isDark = isDark; // export

		const getModules = () => {
			return modules;
		};
		_cls.getModules = getModules;

		const getModuleCount = () => {
			return moduleCount;
		};
		_cls.getModuleCount = getModuleCount; // export

		const make = function() {
			makeImpl(false, getBestMaskPattern());
		};
		_cls.make = make; // export

		const setupPositionProbePattern = function(row, col) {
			for (let r = -1; r <= 7; r++) {
				if (row + r <= -1 || moduleCount <= row + r) continue;
				for (let c = -1; c <= 7; c++) {
					if (col + c <= -1 || moduleCount <= col + c) continue;
					if ((0 <= r && r <= 6 && (c == 0 || c == 6)) || (0 <= c && c <= 6 && (r == 0 || r == 6)) || (2 <= r && r <= 4 && 2 <= c && c <= 4)) {
						modules[row + r][col + c] = true;
					} else {
						modules[row + r][col + c] = false;
					}
				}
			}
		};

		const makeImpl = function(test, maskPattern) {
			moduleCount = typeNumber * 4 + 17;
			modules = new Array(moduleCount);
			for (let row = 0; row < moduleCount; row++) {
				modules[row] = new Array(moduleCount);
				for (let col = 0; col < moduleCount; col++) {
					modules[row][col] = null;
				}
			}
			setupPositionProbePattern(0, 0);
			setupPositionProbePattern(moduleCount - 7, 0);
			setupPositionProbePattern(0, moduleCount - 7);
			setupPositionAdjustPattern();
			setupTimingPattern();
			setupTypeInfo(test, maskPattern);
			if (typeNumber >= 7) {
				setupTypeNumber(test);
			}
			if(dataCache == null) {
				dataCache = createData(typeNumber, errorCorrectLevel, dataList);
			}
			mapData(dataCache, maskPattern);
		};

		const getBestMaskPattern = function() {
			let minLostPoint = 0;
			let pattern = 0;
			for (let i = 0; i < 8; i++) {
				makeImpl(true, i);
				const lostPoint = QRUtil.getLostPoint(_cls);
				if (i == 0 || minLostPoint > lostPoint) {
					minLostPoint = lostPoint;
					pattern = i;
				}
			}
			return pattern;
		};

		const setupTimingPattern = function() {
			for (let r = 8; r < moduleCount - 8; r++) {
				if (modules[r][6] != null) {
					continue;
				}
				modules[r][6] = (r % 2 == 0);
			}
			for (let c = 8; c < moduleCount - 8; c++) {
				if (modules[6][c] != null) {
					continue;
				}
				modules[6][c] = (c % 2 == 0);
			}
		};

		const setupPositionAdjustPattern = function() {
			const pos = QRUtil.getPatternPosition(typeNumber);
			for (let i = 0; i < pos.length; i++) {
				for (let j = 0; j < pos.length; j++) {
					const row = pos[i];
					const col = pos[j];
					if (modules[row][col] != null) {
						continue;
					}
					for (let r = -2; r <= 2; r++) {
						for (let c = -2; c <= 2; c++) {
							if (r == -2 || r == 2 || c == -2 || c == 2 || (r == 0 && c == 0)) {
								modules[row + r][col + c] = true;
							} else {
								modules[row + r][col + c] = false;
							}
						}
					}
				}
			}
		};

		const setupTypeNumber = function(test) {
			const bits = QRUtil.getBCHTypeNumber(typeNumber);
			for (let i = 0; i < 18; i++) {
				let mod = (!test && ((bits >> i) & 1) == 1);
				modules[Math.floor(i / 3)][i % 3 + moduleCount - 8 - 3] = mod;
			}
			for (let i = 0; i < 18; i++) {
				let mod = (!test && ((bits >> i) & 1) == 1);
				modules[i % 3 + moduleCount - 8 - 3][Math.floor(i / 3)] = mod;
			}
		};

		const setupTypeInfo = function(test, maskPattern) {
			const data = (errorCorrectLevel << 3) | maskPattern;
			const bits = QRUtil.getBCHTypeInfo(data);
			for (let i = 0; i < 15; i++) {
				let mod = (!test && ((bits >> i) & 1) == 1);
				if (i < 6) {
					modules[i][8] = mod;
				} else if (i < 8) {
					modules[i + 1][8] = mod;
				} else {
					modules[moduleCount - 15 + i][8] = mod;
				}
			}
			for (let i = 0; i < 15; i++) {
				let mod = (!test && ((bits >> i) & 1) == 1);
				if (i < 8) {
					modules[8][moduleCount - i - 1] = mod;
				} else if (i < 9) {
					modules[8][15 - i - 1 + 1] = mod;
				} else {
					modules[8][15 - i - 1] = mod;
				}
			}
			modules[moduleCount - 8][8] = (!test);
		};

		const mapData = function(data, maskPattern) {
			let inc = -1;
			let row = moduleCount - 1;
			let bitIndex = 7;
			let byteIndex = 0;
			for (let col = moduleCount - 1; col > 0; col -= 2) {
				if (col == 6) col--;
				while (true) {
					for (let c = 0; c < 2; c++) {
						if (modules[row][col - c] == null) {
							let dark = false;
							if (byteIndex < data.length) {
								dark = (((data[byteIndex] >>> bitIndex) & 1) == 1);
							}
							const mask = QRUtil.getMask(maskPattern, row, col - c);
							if (mask) {
								dark = !dark;
							}
							modules[row][col - c] = dark;
							bitIndex--;
							if (bitIndex == -1) {
								byteIndex++;
								bitIndex = 7;
							}
						}
					}
					row += inc;
					if (row < 0 || moduleCount <= row) {
						row -= inc;
						inc = -inc;
						break;
					}
				}
			}
		};

		const createBytes = (buffer, rsBlocks) => {
			let offset = 0;
			let maxDcCount = 0;
			let maxEcCount = 0;
			const dcdata = new Array(rsBlocks.length);
			const ecdata = new Array(rsBlocks.length);
			for (let r = 0; r < rsBlocks.length; r++) {
				const dcCount = rsBlocks[r].dataCount;
				const ecCount = rsBlocks[r].totalCount - dcCount;
				maxDcCount = Math.max(maxDcCount, dcCount);
				maxEcCount = Math.max(maxEcCount, ecCount);
				dcdata[r] = new Array(dcCount);
				for (let i = 0; i < dcdata[r].length; i++) {
					dcdata[r][i] = 0xff & buffer.buffer[i + offset];
				}
				offset += dcCount;
				const rsPoly = QRUtil.getErrorCorrectPolynomial(ecCount);
				const rawPoly = new QRPolynomial(dcdata[r], rsPoly.getLength() - 1);
				const modPoly = rawPoly.mod(rsPoly);
				ecdata[r] = new Array(rsPoly.getLength() - 1);
				for (let i = 0; i < ecdata[r].length; i++) {
					const modIndex = i + modPoly.getLength() - ecdata[r].length;
					ecdata[r][i] = (modIndex >= 0) ? modPoly.getIdx(modIndex) : 0;
				}
			}
			let totalCodeCount = 0;
			for (let i = 0; i < rsBlocks.length; i++) {
				totalCodeCount += rsBlocks[i].totalCount;
			}
			const data = new Array(totalCodeCount);
			let index = 0;
			for (let i = 0; i < maxDcCount; i++) {
				for (let r = 0; r < rsBlocks.length; r++) {
					if (i < dcdata[r].length) {
						data[index++] = dcdata[r][i];
					}
				}
			}
			for (let i = 0; i < maxEcCount; i++) {
				for (let r = 0; r < rsBlocks.length; r++) {
					if (i < ecdata[r].length) {
						data[index++] = ecdata[r][i];
					}
				}
			}
			return data;
		};
		_cls.createBytes = createBytes; // export

		const createData = (typeNumber, errorCorrectLevel, dataList) => {
			const rsBlocks = QRRSBlock.getRSBlocks(typeNumber, errorCorrectLevel);
			const buffer = new QRBitBuffer();
			for (let i = 0; i < dataList.length; i++) {
				const data = dataList[i];
				buffer.put(data.mode, 4);
				buffer.put(data.getLength(), QRUtil.getLengthInBits(data.mode, typeNumber));
				data.write(buffer);
			}
			let totalDataCount = 0;
			for (let i = 0; i < rsBlocks.length; i++) {
				totalDataCount += rsBlocks[i].dataCount;
			}
			if (buffer.getLengthInBits() > totalDataCount * 8) {
				throw new Error(`code length overflow. (${buffer.getLengthInBits()}>${totalDataCount * 8})`);
				return null;
			}
			if (buffer.getLengthInBits() + 4 <= totalDataCount * 8) {
				buffer.put(0, 4);
			}
			while (buffer.getLengthInBits() % 8 != 0) {
				buffer.putBit(false);
			}
			while (true) {
				if (buffer.getLengthInBits() >= totalDataCount * 8) {
					break;
				}
				buffer.put(PAD0, 8);
				if (buffer.getLengthInBits() >= totalDataCount * 8) {
					break;
				}
				buffer.put(PAD1, 8);
			}
			return createBytes(buffer, rsBlocks);
		};
		_cls.createData = createData; // export

	}};
	//--

	//--
	const QRPolynomial = class {
		constructor(num, shift) {
			if (num.length == undefined) {
				throw new Error(`${num.length}/${shift}`);
				return;
			}
			let offset = 0;
			while (offset < num.length && num[offset] == 0) {
				offset++;
			}
			this.num = new Array(num.length - offset + shift);
			for (let i = 0; i < num.length - offset; i++) {
				this.num[i] = num[i + offset];
			}
		}

		getIdx(index) {
			return this.num[index];
		}

		getLength() {
			return this.num.length;
		}

		multiply(e) {
			const num = new Array(this.getLength() + e.getLength() - 1);
			for (let i = 0; i < this.getLength(); i++) {
				for (let j = 0; j < e.getLength(); j++) {
					num[i + j] ^= QRMath.gexp(QRMath.glog(this.getIdx(i)) + QRMath.glog(e.getIdx(j)));
				}
			}
			return new QRPolynomial(num, 0);
		}

		mod(e) {
			if (this.getLength() - e.getLength() < 0) {
				return this;
			}
			const ratio = QRMath.glog(this.getIdx(0)) - QRMath.glog(e.getIdx(0));
			const num = new Array(this.getLength());
			for (var i = 0; i < this.getLength(); i++) {
				num[i] = this.getIdx(i);
			}
			for (var i = 0; i < e.getLength(); i++) {
				num[i] ^= QRMath.gexp(QRMath.glog(e.getIdx(i)) + ratio);
			}
			return new QRPolynomial(num, 0).mod(e);
		}
	};
	//--

	//--
	const QRRSBlock = function(totalCount, dataCount) {
		this.totalCount = totalCount;
		this.dataCount = dataCount;
	};
	//--
	QRRSBlock.RS_BLOCK_TABLE = [
		[1, 26, 19],
		[1, 26, 16],
		[1, 26, 13],
		[1, 26, 9],
		[1, 44, 34],
		[1, 44, 28],
		[1, 44, 22],
		[1, 44, 16],
		[1, 70, 55],
		[1, 70, 44],
		[2, 35, 17],
		[2, 35, 13],
		[1, 100, 80],
		[2, 50, 32],
		[2, 50, 24],
		[4, 25, 9],
		[1, 134, 108],
		[2, 67, 43],
		[2, 33, 15, 2, 34, 16],
		[2, 33, 11, 2, 34, 12],
		[2, 86, 68],
		[4, 43, 27],
		[4, 43, 19],
		[4, 43, 15],
		[2, 98, 78],
		[4, 49, 31],
		[2, 32, 14, 4, 33, 15],
		[4, 39, 13, 1, 40, 14],
		[2, 121, 97],
		[2, 60, 38, 2, 61, 39],
		[4, 40, 18, 2, 41, 19],
		[4, 40, 14, 2, 41, 15],
		[2, 146, 116],
		[3, 58, 36, 2, 59, 37],
		[4, 36, 16, 4, 37, 17],
		[4, 36, 12, 4, 37, 13],
		[2, 86, 68, 2, 87, 69],
		[4, 69, 43, 1, 70, 44],
		[6, 43, 19, 2, 44, 20],
		[6, 43, 15, 2, 44, 16],
		[4, 101, 81],
		[1, 80, 50, 4, 81, 51],
		[4, 50, 22, 4, 51, 23],
		[3, 36, 12, 8, 37, 13],
		[2, 116, 92, 2, 117, 93],
		[6, 58, 36, 2, 59, 37],
		[4, 46, 20, 6, 47, 21],
		[7, 42, 14, 4, 43, 15],
		[4, 133, 107],
		[8, 59, 37, 1, 60, 38],
		[8, 44, 20, 4, 45, 21],
		[12, 33, 11, 4, 34, 12],
		[3, 145, 115, 1, 146, 116],
		[4, 64, 40, 5, 65, 41],
		[11, 36, 16, 5, 37, 17],
		[11, 36, 12, 5, 37, 13],
		[5, 109, 87, 1, 110, 88],
		[5, 65, 41, 5, 66, 42],
		[5, 54, 24, 7, 55, 25],
		[11, 36, 12],
		[5, 122, 98, 1, 123, 99],
		[7, 73, 45, 3, 74, 46],
		[15, 43, 19, 2, 44, 20],
		[3, 45, 15, 13, 46, 16],
		[1, 135, 107, 5, 136, 108],
		[10, 74, 46, 1, 75, 47],
		[1, 50, 22, 15, 51, 23],
		[2, 42, 14, 17, 43, 15],
		[5, 150, 120, 1, 151, 121],
		[9, 69, 43, 4, 70, 44],
		[17, 50, 22, 1, 51, 23],
		[2, 42, 14, 19, 43, 15],
		[3, 141, 113, 4, 142, 114],
		[3, 70, 44, 11, 71, 45],
		[17, 47, 21, 4, 48, 22],
		[9, 39, 13, 16, 40, 14],
		[3, 135, 107, 5, 136, 108],
		[3, 67, 41, 13, 68, 42],
		[15, 54, 24, 5, 55, 25],
		[15, 43, 15, 10, 44, 16],
		[4, 144, 116, 4, 145, 117],
		[17, 68, 42],
		[17, 50, 22, 6, 51, 23],
		[19, 46, 16, 6, 47, 17],
		[2, 139, 111, 7, 140, 112],
		[17, 74, 46],
		[7, 54, 24, 16, 55, 25],
		[34, 37, 13],
		[4, 151, 121, 5, 152, 122],
		[4, 75, 47, 14, 76, 48],
		[11, 54, 24, 14, 55, 25],
		[16, 45, 15, 14, 46, 16],
		[6, 147, 117, 4, 148, 118],
		[6, 73, 45, 14, 74, 46],
		[11, 54, 24, 16, 55, 25],
		[30, 46, 16, 2, 47, 17],
		[8, 132, 106, 4, 133, 107],
		[8, 75, 47, 13, 76, 48],
		[7, 54, 24, 22, 55, 25],
		[22, 45, 15, 13, 46, 16],
		[10, 142, 114, 2, 143, 115],
		[19, 74, 46, 4, 75, 47],
		[28, 50, 22, 6, 51, 23],
		[33, 46, 16, 4, 47, 17],
		[8, 152, 122, 4, 153, 123],
		[22, 73, 45, 3, 74, 46],
		[8, 53, 23, 26, 54, 24],
		[12, 45, 15, 28, 46, 16],
		[3, 147, 117, 10, 148, 118],
		[3, 73, 45, 23, 74, 46],
		[4, 54, 24, 31, 55, 25],
		[11, 45, 15, 31, 46, 16],
		[7, 146, 116, 7, 147, 117],
		[21, 73, 45, 7, 74, 46],
		[1, 53, 23, 37, 54, 24],
		[19, 45, 15, 26, 46, 16],
		[5, 145, 115, 10, 146, 116],
		[19, 75, 47, 10, 76, 48],
		[15, 54, 24, 25, 55, 25],
		[23, 45, 15, 25, 46, 16],
		[13, 145, 115, 3, 146, 116],
		[2, 74, 46, 29, 75, 47],
		[42, 54, 24, 1, 55, 25],
		[23, 45, 15, 28, 46, 16],
		[17, 145, 115],
		[10, 74, 46, 23, 75, 47],
		[10, 54, 24, 35, 55, 25],
		[19, 45, 15, 35, 46, 16],
		[17, 145, 115, 1, 146, 116],
		[14, 74, 46, 21, 75, 47],
		[29, 54, 24, 19, 55, 25],
		[11, 45, 15, 46, 46, 16],
		[13, 145, 115, 6, 146, 116],
		[14, 74, 46, 23, 75, 47],
		[44, 54, 24, 7, 55, 25],
		[59, 46, 16, 1, 47, 17],
		[12, 151, 121, 7, 152, 122],
		[12, 75, 47, 26, 76, 48],
		[39, 54, 24, 14, 55, 25],
		[22, 45, 15, 41, 46, 16],
		[6, 151, 121, 14, 152, 122],
		[6, 75, 47, 34, 76, 48],
		[46, 54, 24, 10, 55, 25],
		[2, 45, 15, 64, 46, 16],
		[17, 152, 122, 4, 153, 123],
		[29, 74, 46, 14, 75, 47],
		[49, 54, 24, 10, 55, 25],
		[24, 45, 15, 46, 46, 16],
		[4, 152, 122, 18, 153, 123],
		[13, 74, 46, 32, 75, 47],
		[48, 54, 24, 14, 55, 25],
		[42, 45, 15, 32, 46, 16],
		[20, 147, 117, 4, 148, 118],
		[40, 75, 47, 7, 76, 48],
		[43, 54, 24, 22, 55, 25],
		[10, 45, 15, 67, 46, 16],
		[19, 148, 118, 6, 149, 119],
		[18, 75, 47, 31, 76, 48],
		[34, 54, 24, 34, 55, 25],
		[20, 45, 15, 61, 46, 16]
	];
	//--
	QRRSBlock.getRSBlocks = (typeNumber, errorCorrectLevel) => {
		const rsBlock = QRRSBlock.getRsBlockTable(typeNumber, errorCorrectLevel);
		if (rsBlock == undefined) {
			throw new Error(`bad rs block @ typeNumber:${typeNumber}/errorCorrectLevel:${errorCorrectLevel}`);
			return null;
		}
		const length = rsBlock.length / 3;
		const list = [];
		for (let i = 0; i < length; i++) {
			const count = rsBlock[i * 3 + 0];
			const totalCount = rsBlock[i * 3 + 1];
			const dataCount = rsBlock[i * 3 + 2];
			for (let j = 0; j < count; j++) {
				list.push(new QRRSBlock(totalCount, dataCount));
			}
		}
		return list;
	};
	QRRSBlock.getRsBlockTable = (typeNumber, errorCorrectLevel) => {
		switch (errorCorrectLevel) {
			case QRErrorCorrectLevel.L:
				return QRRSBlock.RS_BLOCK_TABLE[(typeNumber - 1) * 4 + 0];
			case QRErrorCorrectLevel.M:
				return QRRSBlock.RS_BLOCK_TABLE[(typeNumber - 1) * 4 + 1];
			case QRErrorCorrectLevel.Q:
				return QRRSBlock.RS_BLOCK_TABLE[(typeNumber - 1) * 4 + 2];
			case QRErrorCorrectLevel.H:
				return QRRSBlock.RS_BLOCK_TABLE[(typeNumber - 1) * 4 + 3];
			default:
				return undefined;
		}
	};
	//--

	//--
	const QRBitBuffer = class{
		constructor() {
			this.buffer = [];
			this.length = 0;
		}

		get(index) {
			const bufIndex = Math.floor(index / 8);
			return ((this.buffer[bufIndex] >>> (7 - index % 8)) & 1) == 1;
		}

		put(num, length) {
			for (let i = 0; i < length; i++) {
				this.putBit(((num >>> (length - i - 1)) & 1) == 1);
			}
		}

		getLengthInBits() {
			return this.length;
		}

		putBit(bit) {
			const bufIndex = Math.floor(this.length / 8);
			if (this.buffer.length <= bufIndex) {
				this.buffer.push(0);
			}
			if (bit) {
				this.buffer[bufIndex] |= (0x80 >>> (this.length % 8));
			}
			this.length++;
		}
	};
	//--

	//--
	const QRCodeLimitLength = [
		[17, 14, 11, 7],
		[32, 26, 20, 14],
		[53, 42, 32, 24],
		[78, 62, 46, 34],
		[106, 84, 60, 44],
		[134, 106, 74, 58],
		[154, 122, 86, 64],
		[192, 152, 108, 84],
		[230, 180, 130, 98],
		[271, 213, 151, 119],
		[321, 251, 177, 137],
		[367, 287, 203, 155],
		[425, 331, 241, 177],
		[458, 362, 258, 194],
		[520, 412, 292, 220],
		[586, 450, 322, 250],
		[644, 504, 364, 280],
		[718, 560, 394, 310],
		[792, 624, 442, 338],
		[858, 666, 482, 382],
		[929, 711, 509, 403],
		[1003, 779, 565, 439],
		[1091, 857, 611, 461],
		[1171, 911, 661, 511],
		[1273, 997, 715, 535],
		[1367, 1059, 751, 593],
		[1465, 1125, 805, 625],
		[1528, 1190, 868, 658],
		[1628, 1264, 908, 698],
		[1732, 1370, 982, 742],
		[1840, 1452, 1030, 790],
		[1952, 1538, 1112, 842],
		[2068, 1628, 1168, 898],
		[2188, 1722, 1228, 958],
		[2303, 1809, 1283, 983],
		[2431, 1911, 1351, 1051],
		[2563, 1989, 1423, 1093],
		[2699, 2099, 1499, 1139],
		[2809, 2213, 1579, 1219],
		[2953, 2331, 1663, 1273]
	];
	//--

	//-- Constructor
	const QRCode = class {constructor(msg, opts) {
		const _cls = this;

		const options = {
			dot: true,
			dim: 128,
			pad:  8,
			ecl: 'M',
			pal: ['#777788'],
			swp:  0, // swap the X and Y modules, some users have had issues with the QR Code
		};

		if(typeof(msg) !== 'string') {
			throw new Error('Expected {msg} as string!');
			return;
		}
		if(msg.length === 0) { /* || 7089 < options.content.length */
			throw new Error('Expected {msg} should not be empty!');
			return;
		}

		if((typeof(opts) == 'object') && !!opts) {
			for(let o in opts ) {
				options[ o ] = opts[ o ];
			}
		}
		if(1 !== options.swp) {
			options.swp = 0;
		}

		if( !( 0 < options.dim ) ) {
			options.dim = 256;
			_p$.warn(_N$, 'Expected {dim} value should be higher than zero!');
		}

		if( !( 0 < options.pad ) ) {
			options.pad = 16;
			_p$.warn(_N$, 'Expected {pad} value should be non-negative!');
		}

		if( options.dim < options.pad ) {
			options.pad = options.dim;
			_p$.warn(_N$, 'Expected {pad} value could not be bigger than {dim} value');
		}

		const _checkColor = function( c ) {
			return /^#[0-9a-f]{3}(?:[0-9a-f]{3})?$/i.test( c );
		};

		const _getErrorCorrectLevel = function( ecl ) {
			switch( ecl ) {
				case 'L': return QRErrorCorrectLevel.L;
				case 'M': return QRErrorCorrectLevel.M;
				case 'Q': return QRErrorCorrectLevel.Q;
				case 'H': return QRErrorCorrectLevel.H;
				default:
					_p$.warn(_N$, `Unknwon error correction level: ${ecl}\nQRErrorCorrectLevel.L was used instead`);
					return QRErrorCorrectLevel.L;
			}
		};

		const _getTypeNumber = function( msg, ecl ) {
			let type = 1;
			let limit = 0;
			const l1 = _getUTF8Length( msg );
			const l2 = QRCodeLimitLength.length;

			for( let i = 0; i <= l2; i++ ) {
				const table = QRCodeLimitLength[ i ];
				if(!table) {
					throw new Error(`Content too long: expected ${limit} but got ${l1}` );
					return null;
				}
				switch( ecl ) {
					case "L": limit = table[ 0 ]; break;
					case "M": limit = table[ 1 ]; break;
					case "Q": limit = table[ 2 ]; break;
					case "H": limit = table[ 3 ]; break;
					default: limit = table[ 0 ];
				}
				if( l1 <= limit) {
					break;
				}
				type++;
			}
			if( type > QRCodeLimitLength.length ) {
				throw new Error('Content too long');
				return null;
			}
			return type;
		};

		const _getUTF8Length = function( msg ) {
			const res = encodeURIComponent(msg).replace(/%([0-9A-Fa-f]{2})/g,
				(match, p1) => { return '.'; }
			);
			return res.length + ( res.length != msg ? 3 : 0);
		};

		if( !_checkColor( options.pal[ 0 ] ) || options.pal[ 1 ] && !_checkColor( options.pal[ 1 ] ) ) {
			options.pal = ['#900', '#fff8f8'];
			_p$.warn(_N$, 'Expected {pal} value for foreground and/or background is not valid');
		}

		//Generate QR Code matrix
		const ecl = _getErrorCorrectLevel( options.ecl ), type = _getTypeNumber( msg, options.ecl );

		const qrcode = new QRCodeModel( type, ecl );
		qrcode.addData( msg );
		qrcode.make();

		// Generates QR Code as SVG image
		const getSvg = () => {
			let res;

			const svg = {
				 el(n, a) {
						n = document.createElementNS( svg.ns, n );
						for( const o in a || {} ) {
							n.setAttribute( o, a[ o ] );
						}
						return n;
					}
				,ns	: 'http://www.w3.org/2000/svg'
			};

			const opt = options;
			const bit = qrcode.getModules();
			const len = bit.length;
			const swp = opt.swp;  // ( 1 == opt.swp ) ? 1 : 0,

			const _fg = opt.pal[ 0 ];
			const _bg = opt.pal[ 1 ];
			const _d = opt.dim;
			const p_ = opt.pad;
			const _p = ( p_ > _d ) ? _d : p_;
			const _s = ( ( _d - ( 2 * _p ) ) / len ).toFixed( 4 );
			const _m = [ _s, 0, 0, _s, _p, _p ];

			const ellipseAttrsToPath = (cx, cy, rx, ry) => `M${cx-rx},${cy}a${rx},${ry} 0 1,0 ${rx*2},0a${rx},${ry} 0 1,0 -${rx*2},0 Z`
			const squareAttrsToPath  = (cx, cy) => `M${cx},${cy}h1v1h-1v-1z`;

			let _path = '';
			for( let y = 0; y < len; y++ ) {
				for( let x = 0; x < len; x++ ) {
					if( bit[ x ][ y ] ) {
						let _y = y, _x = x;
						if( swp ) {
							_y = x,
							_x = y;
						}
						if(!!opt.dot) {
							_path += ellipseAttrsToPath(_x, _y, 0.55, 0.55); // ellipse
						} else {
							_path += squareAttrsToPath(_x, _y); // squares
						}
					}
				}
			}

			res = svg.el('svg', {
				'viewBox'			: [ 0, 0, _d, _d ].join(' '),
				'width'				:  _d,
				'height'			:  _d,
				'fill'				:  _fg,
				'shape-rendering'	: (!!opt.dot) ? 'geometricPrecision' : 'crispEdges',
				'xmlns'				:  svg.ns,
				'version'			: '1.1',
			});

			if( _bg ) {
				res.appendChild( svg.el('path', {
					'fill':  			_bg,
					'd': 				`M0,0V${_d}H${_d}V0H0Z`,
				}));
			}

			res.appendChild( svg.el('path', {
				'transform': 			`matrix(${_m})`,
				'd': 					_path,
			//	'stroke-linecap': 		'round',
			//	'stroke-linejoin': 		'bevel',
			//	'stroke-width':			1,
			}));

			return String(res.outerHTML || '');
		};
		_cls.getSvg = getSvg; // export

	}};
	//--

	//--
	const getAsSvg = (msg, opts) => {
		const qr = new QRCode(msg, opts);
		return String(qr.getSvg());
	};
	_C$.getAsSvg = getAsSvg; // export
	//--

}}; //END CLASS

smartQRCode.secureClass(); // implements class security

if(typeof(window) != 'undefined') {
	window.smartQRCode = smartQRCode; // global export
} //end if

// #END
