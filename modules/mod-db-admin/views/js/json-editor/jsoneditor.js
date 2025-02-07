/*
 * jsoneditor.js
 *
 * JSONEditor is a web-based tool to view, edit, and format JSON.
 * It shows data a clear, editable treeview.
 *
 * Supported browsers: Chrome, Firefox, Safari, Edge, Opera
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy
 * of the License at http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 *
 * Copyright (c) 2011-present Jos de Jong, http://jsoneditoronline.org
 * @author  Jos de Jong, <wjosdejong@gmail.com>
 * @version 4.2.1.1
 *
 * (c) 2025-present unix-world.org
 * esbuild
 *
 */

(() => {
	var __getOwnPropNames = Object.getOwnPropertyNames;
	var __require = /* @__PURE__ */ ((x) => typeof require !== "undefined" ? require : typeof Proxy !== "undefined" ? new Proxy(x, {
		get: (a, b) => (typeof require !== "undefined" ? require : a)[b]
	}) : x)(function(x) {
		if (typeof require !== "undefined") return require.apply(this, arguments);
		throw Error('Dynamic require of "' + x + '" is not supported');
	});
	var __commonJS = (cb, mod) => function __require2() {
		return mod || (0, cb[__getOwnPropNames(cb)[0]])((mod = { exports: {} }).exports, mod), mod.exports;
	};

	// src/js/Highlighter.js
	var require_Highlighter = __commonJS({
		"src/js/Highlighter.js"(exports, module) {
			function Highlighter() {
				this.locked = false;
			}
			Highlighter.prototype.highlight = function(node) {
				if (this.locked) {
					return;
				}
				if (this.node != node) {
					if (this.node) {
						this.node.setHighlight(false);
					}
					this.node = node;
					this.node.setHighlight(true);
				}
				this._cancelUnhighlight();
			};
			Highlighter.prototype.unhighlight = function() {
				if (this.locked) {
					return;
				}
				var me = this;
				if (this.node) {
					this._cancelUnhighlight();
					this.unhighlightTimer = setTimeout(function() {
						me.node.setHighlight(false);
						me.node = void 0;
						me.unhighlightTimer = void 0;
					}, 0);
				}
			};
			Highlighter.prototype._cancelUnhighlight = function() {
				if (this.unhighlightTimer) {
					clearTimeout(this.unhighlightTimer);
					this.unhighlightTimer = void 0;
				}
			};
			Highlighter.prototype.lock = function() {
				this.locked = true;
			};
			Highlighter.prototype.unlock = function() {
				this.locked = false;
			};
			module.exports = Highlighter;
		}
	});

	// node_modules/jsonlint/lib/jsonlint.js
	var require_jsonlint = __commonJS({
		"node_modules/jsonlint/lib/jsonlint.js"(exports) {
			var jsonlint = function() {
				var parser = {
					trace: function trace() {
					},
					yy: {},
					symbols_: { "error": 2, "JSONString": 3, "STRING": 4, "JSONNumber": 5, "NUMBER": 6, "JSONNullLiteral": 7, "NULL": 8, "JSONBooleanLiteral": 9, "TRUE": 10, "FALSE": 11, "JSONText": 12, "JSONValue": 13, "EOF": 14, "JSONObject": 15, "JSONArray": 16, "{": 17, "}": 18, "JSONMemberList": 19, "JSONMember": 20, ":": 21, ",": 22, "[": 23, "]": 24, "JSONElementList": 25, "$accept": 0, "$end": 1 },
					terminals_: { 2: "error", 4: "STRING", 6: "NUMBER", 8: "NULL", 10: "TRUE", 11: "FALSE", 14: "EOF", 17: "{", 18: "}", 21: ":", 22: ",", 23: "[", 24: "]" },
					productions_: [0, [3, 1], [5, 1], [7, 1], [9, 1], [9, 1], [12, 2], [13, 1], [13, 1], [13, 1], [13, 1], [13, 1], [13, 1], [15, 2], [15, 3], [20, 3], [19, 1], [19, 3], [16, 2], [16, 3], [25, 1], [25, 3]],
					performAction: function anonymous(yytext, yyleng, yylineno, yy, yystate, $$, _$) {
						var $0 = $$.length - 1;
						switch (yystate) {
							case 1:
								this.$ = yytext.replace(/\\(\\|")/g, "$1").replace(/\\n/g, "\n").replace(/\\r/g, "\r").replace(/\\t/g, "	").replace(/\\v/g, "\v").replace(/\\f/g, "\f").replace(/\\b/g, "\b");
								break;
							case 2:
								this.$ = Number(yytext);
								break;
							case 3:
								this.$ = null;
								break;
							case 4:
								this.$ = true;
								break;
							case 5:
								this.$ = false;
								break;
							case 6:
								return this.$ = $$[$0 - 1];
								break;
							case 13:
								this.$ = {};
								break;
							case 14:
								this.$ = $$[$0 - 1];
								break;
							case 15:
								this.$ = [$$[$0 - 2], $$[$0]];
								break;
							case 16:
								this.$ = {};
								this.$[$$[$0][0]] = $$[$0][1];
								break;
							case 17:
								this.$ = $$[$0 - 2];
								$$[$0 - 2][$$[$0][0]] = $$[$0][1];
								break;
							case 18:
								this.$ = [];
								break;
							case 19:
								this.$ = $$[$0 - 1];
								break;
							case 20:
								this.$ = [$$[$0]];
								break;
							case 21:
								this.$ = $$[$0 - 2];
								$$[$0 - 2].push($$[$0]);
								break;
						}
					},
					table: [{ 3: 5, 4: [1, 12], 5: 6, 6: [1, 13], 7: 3, 8: [1, 9], 9: 4, 10: [1, 10], 11: [1, 11], 12: 1, 13: 2, 15: 7, 16: 8, 17: [1, 14], 23: [1, 15] }, { 1: [3] }, { 14: [1, 16] }, { 14: [2, 7], 18: [2, 7], 22: [2, 7], 24: [2, 7] }, { 14: [2, 8], 18: [2, 8], 22: [2, 8], 24: [2, 8] }, { 14: [2, 9], 18: [2, 9], 22: [2, 9], 24: [2, 9] }, { 14: [2, 10], 18: [2, 10], 22: [2, 10], 24: [2, 10] }, { 14: [2, 11], 18: [2, 11], 22: [2, 11], 24: [2, 11] }, { 14: [2, 12], 18: [2, 12], 22: [2, 12], 24: [2, 12] }, { 14: [2, 3], 18: [2, 3], 22: [2, 3], 24: [2, 3] }, { 14: [2, 4], 18: [2, 4], 22: [2, 4], 24: [2, 4] }, { 14: [2, 5], 18: [2, 5], 22: [2, 5], 24: [2, 5] }, { 14: [2, 1], 18: [2, 1], 21: [2, 1], 22: [2, 1], 24: [2, 1] }, { 14: [2, 2], 18: [2, 2], 22: [2, 2], 24: [2, 2] }, { 3: 20, 4: [1, 12], 18: [1, 17], 19: 18, 20: 19 }, { 3: 5, 4: [1, 12], 5: 6, 6: [1, 13], 7: 3, 8: [1, 9], 9: 4, 10: [1, 10], 11: [1, 11], 13: 23, 15: 7, 16: 8, 17: [1, 14], 23: [1, 15], 24: [1, 21], 25: 22 }, { 1: [2, 6] }, { 14: [2, 13], 18: [2, 13], 22: [2, 13], 24: [2, 13] }, { 18: [1, 24], 22: [1, 25] }, { 18: [2, 16], 22: [2, 16] }, { 21: [1, 26] }, { 14: [2, 18], 18: [2, 18], 22: [2, 18], 24: [2, 18] }, { 22: [1, 28], 24: [1, 27] }, { 22: [2, 20], 24: [2, 20] }, { 14: [2, 14], 18: [2, 14], 22: [2, 14], 24: [2, 14] }, { 3: 20, 4: [1, 12], 20: 29 }, { 3: 5, 4: [1, 12], 5: 6, 6: [1, 13], 7: 3, 8: [1, 9], 9: 4, 10: [1, 10], 11: [1, 11], 13: 30, 15: 7, 16: 8, 17: [1, 14], 23: [1, 15] }, { 14: [2, 19], 18: [2, 19], 22: [2, 19], 24: [2, 19] }, { 3: 5, 4: [1, 12], 5: 6, 6: [1, 13], 7: 3, 8: [1, 9], 9: 4, 10: [1, 10], 11: [1, 11], 13: 31, 15: 7, 16: 8, 17: [1, 14], 23: [1, 15] }, { 18: [2, 17], 22: [2, 17] }, { 18: [2, 15], 22: [2, 15] }, { 22: [2, 21], 24: [2, 21] }],
					defaultActions: { 16: [2, 6] },
					parseError: function parseError(str, hash) {
						throw new Error(str);
					},
					parse: function parse(input) {
						var self = this, stack = [0], vstack = [null], lstack = [], table = this.table, yytext = "", yylineno = 0, yyleng = 0, recovering = 0, TERROR = 2, EOF = 1;
						this.lexer.setInput(input);
						this.lexer.yy = this.yy;
						this.yy.lexer = this.lexer;
						if (typeof this.lexer.yylloc == "undefined")
							this.lexer.yylloc = {};
						var yyloc = this.lexer.yylloc;
						lstack.push(yyloc);
						if (typeof this.yy.parseError === "function")
							this.parseError = this.yy.parseError;
						function popStack(n) {
							stack.length = stack.length - 2 * n;
							vstack.length = vstack.length - n;
							lstack.length = lstack.length - n;
						}
						function lex() {
							var token;
							token = self.lexer.lex() || 1;
							if (typeof token !== "number") {
								token = self.symbols_[token] || token;
							}
							return token;
						}
						var symbol, preErrorSymbol, state, action, a, r, yyval = {}, p, len, newState, expected;
						while (true) {
							state = stack[stack.length - 1];
							if (this.defaultActions[state]) {
								action = this.defaultActions[state];
							} else {
								if (symbol == null)
									symbol = lex();
								action = table[state] && table[state][symbol];
							}
							_handle_error:
								if (typeof action === "undefined" || !action.length || !action[0]) {
									if (!recovering) {
										expected = [];
										for (p in table[state]) if (this.terminals_[p] && p > 2) {
											expected.push("'" + this.terminals_[p] + "'");
										}
										var errStr = "";
										if (this.lexer.showPosition) {
											errStr = "Parse error on line " + (yylineno + 1) + ":\n" + this.lexer.showPosition() + "\nExpecting " + expected.join(", ") + ", got '" + this.terminals_[symbol] + "'";
										} else {
											errStr = "Parse error on line " + (yylineno + 1) + ": Unexpected " + (symbol == 1 ? "end of input" : "'" + (this.terminals_[symbol] || symbol) + "'");
										}
										this.parseError(
											errStr,
											{ text: this.lexer.match, token: this.terminals_[symbol] || symbol, line: this.lexer.yylineno, loc: yyloc, expected }
										);
									}
									if (recovering == 3) {
										if (symbol == EOF) {
											throw new Error(errStr || "Parsing halted.");
										}
										yyleng = this.lexer.yyleng;
										yytext = this.lexer.yytext;
										yylineno = this.lexer.yylineno;
										yyloc = this.lexer.yylloc;
										symbol = lex();
									}
									while (1) {
										if (TERROR.toString() in table[state]) {
											break;
										}
										if (state == 0) {
											throw new Error(errStr || "Parsing halted.");
										}
										popStack(1);
										state = stack[stack.length - 1];
									}
									preErrorSymbol = symbol;
									symbol = TERROR;
									state = stack[stack.length - 1];
									action = table[state] && table[state][TERROR];
									recovering = 3;
								}
							if (action[0] instanceof Array && action.length > 1) {
								throw new Error("Parse Error: multiple actions possible at state: " + state + ", token: " + symbol);
							}
							switch (action[0]) {
								case 1:
									stack.push(symbol);
									vstack.push(this.lexer.yytext);
									lstack.push(this.lexer.yylloc);
									stack.push(action[1]);
									symbol = null;
									if (!preErrorSymbol) {
										yyleng = this.lexer.yyleng;
										yytext = this.lexer.yytext;
										yylineno = this.lexer.yylineno;
										yyloc = this.lexer.yylloc;
										if (recovering > 0)
											recovering--;
									} else {
										symbol = preErrorSymbol;
										preErrorSymbol = null;
									}
									break;
								case 2:
									len = this.productions_[action[1]][1];
									yyval.$ = vstack[vstack.length - len];
									yyval._$ = {
										first_line: lstack[lstack.length - (len || 1)].first_line,
										last_line: lstack[lstack.length - 1].last_line,
										first_column: lstack[lstack.length - (len || 1)].first_column,
										last_column: lstack[lstack.length - 1].last_column
									};
									r = this.performAction.call(yyval, yytext, yyleng, yylineno, this.yy, action[1], vstack, lstack);
									if (typeof r !== "undefined") {
										return r;
									}
									if (len) {
										stack = stack.slice(0, -1 * len * 2);
										vstack = vstack.slice(0, -1 * len);
										lstack = lstack.slice(0, -1 * len);
									}
									stack.push(this.productions_[action[1]][0]);
									vstack.push(yyval.$);
									lstack.push(yyval._$);
									newState = table[stack[stack.length - 2]][stack[stack.length - 1]];
									stack.push(newState);
									break;
								case 3:
									return true;
							}
						}
						return true;
					}
				};
				var lexer = function() {
					var lexer2 = {
						EOF: 1,
						parseError: function parseError(str, hash) {
							if (this.yy.parseError) {
								this.yy.parseError(str, hash);
							} else {
								throw new Error(str);
							}
						},
						setInput: function(input) {
							this._input = input;
							this._more = this._less = this.done = false;
							this.yylineno = this.yyleng = 0;
							this.yytext = this.matched = this.match = "";
							this.conditionStack = ["INITIAL"];
							this.yylloc = { first_line: 1, first_column: 0, last_line: 1, last_column: 0 };
							return this;
						},
						input: function() {
							var ch = this._input[0];
							this.yytext += ch;
							this.yyleng++;
							this.match += ch;
							this.matched += ch;
							var lines = ch.match(/\n/);
							if (lines) this.yylineno++;
							this._input = this._input.slice(1);
							return ch;
						},
						unput: function(ch) {
							this._input = ch + this._input;
							return this;
						},
						more: function() {
							this._more = true;
							return this;
						},
						less: function(n) {
							this._input = this.match.slice(n) + this._input;
						},
						pastInput: function() {
							var past = this.matched.substr(0, this.matched.length - this.match.length);
							return (past.length > 20 ? "..." : "") + past.substr(-20).replace(/\n/g, "");
						},
						upcomingInput: function() {
							var next = this.match;
							if (next.length < 20) {
								next += this._input.substr(0, 20 - next.length);
							}
							return (next.substr(0, 20) + (next.length > 20 ? "..." : "")).replace(/\n/g, "");
						},
						showPosition: function() {
							var pre = this.pastInput();
							var c = new Array(pre.length + 1).join("-");
							return pre + this.upcomingInput() + "\n" + c + "^";
						},
						next: function() {
							if (this.done) {
								return this.EOF;
							}
							if (!this._input) this.done = true;
							var token, match, tempMatch, index, col, lines;
							if (!this._more) {
								this.yytext = "";
								this.match = "";
							}
							var rules = this._currentRules();
							for (var i = 0; i < rules.length; i++) {
								tempMatch = this._input.match(this.rules[rules[i]]);
								if (tempMatch && (!match || tempMatch[0].length > match[0].length)) {
									match = tempMatch;
									index = i;
									if (!this.options.flex) break;
								}
							}
							if (match) {
								lines = match[0].match(/\n.*/g);
								if (lines) this.yylineno += lines.length;
								this.yylloc = {
									first_line: this.yylloc.last_line,
									last_line: this.yylineno + 1,
									first_column: this.yylloc.last_column,
									last_column: lines ? lines[lines.length - 1].length - 1 : this.yylloc.last_column + match[0].length
								};
								this.yytext += match[0];
								this.match += match[0];
								this.yyleng = this.yytext.length;
								this._more = false;
								this._input = this._input.slice(match[0].length);
								this.matched += match[0];
								token = this.performAction.call(this, this.yy, this, rules[index], this.conditionStack[this.conditionStack.length - 1]);
								if (this.done && this._input) this.done = false;
								if (token) return token;
								else return;
							}
							if (this._input === "") {
								return this.EOF;
							} else {
								this.parseError(
									"Lexical error on line " + (this.yylineno + 1) + ". Unrecognized text.\n" + this.showPosition(),
									{ text: "", token: null, line: this.yylineno }
								);
							}
						},
						lex: function lex() {
							var r = this.next();
							if (typeof r !== "undefined") {
								return r;
							} else {
								return this.lex();
							}
						},
						begin: function begin(condition) {
							this.conditionStack.push(condition);
						},
						popState: function popState() {
							return this.conditionStack.pop();
						},
						_currentRules: function _currentRules() {
							return this.conditions[this.conditionStack[this.conditionStack.length - 1]].rules;
						},
						topState: function() {
							return this.conditionStack[this.conditionStack.length - 2];
						},
						pushState: function begin(condition) {
							this.begin(condition);
						}
					};
					lexer2.options = {};
					lexer2.performAction = function anonymous(yy, yy_, $avoiding_name_collisions, YY_START) {
						var YYSTATE = YY_START;
						switch ($avoiding_name_collisions) {
							case 0:
								break;
							case 1:
								return 6;
								break;
							case 2:
								yy_.yytext = yy_.yytext.substr(1, yy_.yyleng - 2);
								return 4;
								break;
							case 3:
								return 17;
								break;
							case 4:
								return 18;
								break;
							case 5:
								return 23;
								break;
							case 6:
								return 24;
								break;
							case 7:
								return 22;
								break;
							case 8:
								return 21;
								break;
							case 9:
								return 10;
								break;
							case 10:
								return 11;
								break;
							case 11:
								return 8;
								break;
							case 12:
								return 14;
								break;
							case 13:
								return "INVALID";
								break;
						}
					};
					lexer2.rules = [/^(?:\s+)/, /^(?:(-?([0-9]|[1-9][0-9]+))(\.[0-9]+)?([eE][-+]?[0-9]+)?\b)/, /^(?:"(?:\\[\\"bfnrt/]|\\u[a-fA-F0-9]{4}|[^\\\0-\x09\x0a-\x1f"])*")/, /^(?:\{)/, /^(?:\})/, /^(?:\[)/, /^(?:\])/, /^(?:,)/, /^(?::)/, /^(?:true\b)/, /^(?:false\b)/, /^(?:null\b)/, /^(?:$)/, /^(?:.)/];
					lexer2.conditions = { "INITIAL": { "rules": [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13], "inclusive": true } };
					;
					return lexer2;
				}();
				parser.lexer = lexer;
				return parser;
			}();
			if (typeof __require !== "undefined" && typeof exports !== "undefined") {
				exports.parser = jsonlint;
				exports.parse = jsonlint.parse.bind(jsonlint);
			}
		}
	});

	// src/js/util.js
	var require_util = __commonJS({
		"src/js/util.js"(exports) {
			var jsonlint = require_jsonlint();
			exports.parse = function parse(jsonString) {
				try {
					return JSON.parse(jsonString);
				} catch (err) {
					exports.validate(jsonString);
					throw err;
				}
			};
			exports.sanitize = function(jsString) {
				var chars = [];
				var i = 0;
				var match = jsString.match(/^\s*(\/\*(.|[\r\n])*?\*\/)?\s*[\da-zA-Z_$]+\s*\(([\s\S]*)\)\s*;?\s*$/);
				if (match) {
					jsString = match[3];
				}
				function curr() {
					return jsString.charAt(i);
				}
				function next() {
					return jsString.charAt(i + 1);
				}
				function prev() {
					return jsString.charAt(i - 1);
				}
				function prevIsBrace() {
					var ii = i - 1;
					while (ii >= 0) {
						var cc = jsString.charAt(ii);
						if (cc === "{") {
							return true;
						} else if (cc === " " || cc === "\n" || cc === "\r") {
							ii--;
						} else {
							return false;
						}
					}
					return false;
				}
				function skipComment() {
					i += 2;
					while (i < jsString.length && (curr() !== "*" || next() !== "/")) {
						i++;
					}
					i += 2;
				}
				function parseString(quote) {
					chars.push('"');
					i++;
					var c2 = curr();
					while (i < jsString.length && c2 !== quote) {
						if (c2 === '"' && prev() !== "\\") {
							chars.push("\\");
						}
						if (c2 === "\\") {
							i++;
							c2 = curr();
							if (c2 !== "'") {
								chars.push("\\");
							}
						}
						chars.push(c2);
						i++;
						c2 = curr();
					}
					if (c2 === quote) {
						chars.push('"');
						i++;
					}
				}
				function parseKey() {
					var specialValues = ["null", "true", "false"];
					var key = "";
					var c2 = curr();
					var regexp = /[a-zA-Z_$\d]/;
					while (regexp.test(c2)) {
						key += c2;
						i++;
						c2 = curr();
					}
					if (specialValues.indexOf(key) === -1) {
						chars.push('"' + key + '"');
					} else {
						chars.push(key);
					}
				}
				while (i < jsString.length) {
					var c = curr();
					if (c === "/" && next() === "*") {
						skipComment();
					} else if (c === "'" || c === '"') {
						parseString(c);
					} else if (/[a-zA-Z_$]/.test(c) && prevIsBrace()) {
						parseKey();
					} else {
						chars.push(c);
						i++;
					}
				}
				return chars.join("");
			};
			exports.validate = function validate(jsonString) {
				if (typeof jsonlint != "undefined") {
					jsonlint.parse(jsonString);
				} else {
					JSON.parse(jsonString);
				}
			};
			exports.extend = function extend(a, b) {
				for (var prop in b) {
					if (b.hasOwnProperty(prop)) {
						a[prop] = b[prop];
					}
				}
				return a;
			};
			exports.clear = function clear(a) {
				for (var prop in a) {
					if (a.hasOwnProperty(prop)) {
						delete a[prop];
					}
				}
				return a;
			};
			exports.log = function log(args) {
				if (typeof console !== "undefined" && typeof console.log === "function") {
					console.log.apply(console, arguments);
				}
			};
			exports.type = function type(object) {
				if (object === null) {
					return "null";
				}
				if (object === void 0) {
					return "undefined";
				}
				if (object instanceof Number || typeof object === "number") {
					return "number";
				}
				if (object instanceof String || typeof object === "string") {
					return "string";
				}
				if (object instanceof Boolean || typeof object === "boolean") {
					return "boolean";
				}
				if (object instanceof RegExp || typeof object === "regexp") {
					return "regexp";
				}
				if (exports.isArray(object)) {
					return "array";
				}
				return "object";
			};
			var isUrlRegex = /^https?:\/\/\S+$/;
			exports.isUrl = function isUrl(text) {
				return (typeof text == "string" || text instanceof String) && isUrlRegex.test(text);
			};
			exports.isArray = function(obj) {
				return Object.prototype.toString.call(obj) === "[object Array]";
			};
			exports.getAbsoluteLeft = function getAbsoluteLeft(elem) {
				var rect = elem.getBoundingClientRect();
				return rect.left + window.pageXOffset || document.scrollLeft || 0;
			};
			exports.getAbsoluteTop = function getAbsoluteTop(elem) {
				var rect = elem.getBoundingClientRect();
				return rect.top + window.pageYOffset || document.scrollTop || 0;
			};
			exports.addClassName = function addClassName(elem, className) {
				var classes = elem.className.split(" ");
				if (classes.indexOf(className) == -1) {
					classes.push(className);
					elem.className = classes.join(" ");
				}
			};
			exports.removeClassName = function removeClassName(elem, className) {
				var classes = elem.className.split(" ");
				var index = classes.indexOf(className);
				if (index != -1) {
					classes.splice(index, 1);
					elem.className = classes.join(" ");
				}
			};
			exports.stripFormatting = function stripFormatting(divElement) {
				var childs = divElement.childNodes;
				for (var i = 0, iMax = childs.length; i < iMax; i++) {
					var child = childs[i];
					if (child.style) {
						child.removeAttribute("style");
					}
					var attributes = child.attributes;
					if (attributes) {
						for (var j = attributes.length - 1; j >= 0; j--) {
							var attribute = attributes[j];
							if (attribute.specified === true) {
								child.removeAttribute(attribute.name);
							}
						}
					}
					exports.stripFormatting(child);
				}
			};
			exports.setEndOfContentEditable = function setEndOfContentEditable(contentEditableElement) {
				var range, selection;
				if (document.createRange) {
					range = document.createRange();
					range.selectNodeContents(contentEditableElement);
					range.collapse(false);
					selection = window.getSelection();
					selection.removeAllRanges();
					selection.addRange(range);
				}
			};
			exports.selectContentEditable = function selectContentEditable(contentEditableElement) {
				if (!contentEditableElement || contentEditableElement.nodeName != "DIV") {
					return;
				}
				var sel, range;
				if (window.getSelection && document.createRange) {
					range = document.createRange();
					range.selectNodeContents(contentEditableElement);
					sel = window.getSelection();
					sel.removeAllRanges();
					sel.addRange(range);
				}
			};
			exports.getSelection = function getSelection() {
				if (window.getSelection) {
					var sel = window.getSelection();
					if (sel.getRangeAt && sel.rangeCount) {
						return sel.getRangeAt(0);
					}
				}
				return null;
			};
			exports.setSelection = function setSelection(range) {
				if (range) {
					if (window.getSelection) {
						var sel = window.getSelection();
						sel.removeAllRanges();
						sel.addRange(range);
					}
				}
			};
			exports.getSelectionOffset = function getSelectionOffset() {
				var range = exports.getSelection();
				if (range && "startOffset" in range && "endOffset" in range && range.startContainer && range.startContainer == range.endContainer) {
					return {
						startOffset: range.startOffset,
						endOffset: range.endOffset,
						container: range.startContainer.parentNode
					};
				}
				return null;
			};
			exports.setSelectionOffset = function setSelectionOffset(params) {
				if (document.createRange && window.getSelection) {
					var selection = window.getSelection();
					if (selection) {
						var range = document.createRange();
						range.setStart(params.container.firstChild, params.startOffset);
						range.setEnd(params.container.firstChild, params.endOffset);
						exports.setSelection(range);
					}
				}
			};
			exports.getInnerText = function getInnerText(element, buffer) {
				var first = buffer == void 0;
				if (first) {
					buffer = {
						"text": "",
						"flush": function() {
							var text = this.text;
							this.text = "";
							return text;
						},
						"set": function(text) {
							this.text = text;
						}
					};
				}
				if (element.nodeValue) {
					return buffer.flush() + element.nodeValue;
				}
				if (element.hasChildNodes()) {
					var childNodes = element.childNodes;
					var innerText = "";
					for (var i = 0, iMax = childNodes.length; i < iMax; i++) {
						var child = childNodes[i];
						if (child.nodeName == "DIV" || child.nodeName == "P") {
							var prevChild = childNodes[i - 1];
							var prevName = prevChild ? prevChild.nodeName : void 0;
							if (prevName && prevName != "DIV" && prevName != "P" && prevName != "BR") {
								innerText += "\n";
								buffer.flush();
							}
							innerText += exports.getInnerText(child, buffer);
							buffer.set("\n");
						} else if (child.nodeName == "BR") {
							innerText += buffer.flush();
							buffer.set("\n");
						} else {
							innerText += exports.getInnerText(child, buffer);
						}
					}
					return innerText;
				} else {
					if (element.nodeName == "P" && exports.getInternetExplorerVersion() != -1) {
						return buffer.flush();
					}
				}
				return "";
			};
			exports.getInternetExplorerVersion = function getInternetExplorerVersion() {
				if (_ieVersion == -1) {
					var rv = -1;
					if (navigator.appName == "Microsoft Internet Explorer") {
						var ua = navigator.userAgent;
						var re = new RegExp("MSIE ([0-9]{1,}[.0-9]{0,})");
						if (re.exec(ua) != null) {
							rv = parseFloat(RegExp.$1);
						}
					}
					_ieVersion = rv;
				}
				return _ieVersion;
			};
			exports.isFirefox = function isFirefox() {
				return navigator.userAgent.indexOf("Firefox") != -1;
			};
			var _ieVersion = -1;
			exports.addEventListener = function addEventListener(element, action, listener, useCapture) {
				if (element.addEventListener) {
					if (useCapture === void 0)
						useCapture = false;
					if (action === "mousewheel" && exports.isFirefox()) {
						action = "DOMMouseScroll";
					}
					element.addEventListener(action, listener, useCapture);
					return listener;
				} else if (element.attachEvent) {
					var f = function() {
						return listener.call(element, window.event);
					};
					element.attachEvent("on" + action, f);
					return f;
				}
			};
			exports.removeEventListener = function removeEventListener(element, action, listener, useCapture) {
				if (element.removeEventListener) {
					if (useCapture === void 0)
						useCapture = false;
					if (action === "mousewheel" && exports.isFirefox()) {
						action = "DOMMouseScroll";
					}
					element.removeEventListener(action, listener, useCapture);
				} else if (element.detachEvent) {
					element.detachEvent("on" + action, listener);
				}
			};
		}
	});

	// src/js/History.js
	var require_History = __commonJS({
		"src/js/History.js"(exports, module) {
			var util = require_util();
			function History(editor) {
				this.editor = editor;
				this.clear();
				this.actions = {
					"editField": {
						"undo": function(params) {
							params.node.updateField(params.oldValue);
						},
						"redo": function(params) {
							params.node.updateField(params.newValue);
						}
					},
					"editValue": {
						"undo": function(params) {
							params.node.updateValue(params.oldValue);
						},
						"redo": function(params) {
							params.node.updateValue(params.newValue);
						}
					},
					"appendNode": {
						"undo": function(params) {
							params.parent.removeChild(params.node);
						},
						"redo": function(params) {
							params.parent.appendChild(params.node);
						}
					},
					"insertBeforeNode": {
						"undo": function(params) {
							params.parent.removeChild(params.node);
						},
						"redo": function(params) {
							params.parent.insertBefore(params.node, params.beforeNode);
						}
					},
					"insertAfterNode": {
						"undo": function(params) {
							params.parent.removeChild(params.node);
						},
						"redo": function(params) {
							params.parent.insertAfter(params.node, params.afterNode);
						}
					},
					"removeNode": {
						"undo": function(params) {
							var parent = params.parent;
							var beforeNode = parent.childs[params.index] || parent.append;
							parent.insertBefore(params.node, beforeNode);
						},
						"redo": function(params) {
							params.parent.removeChild(params.node);
						}
					},
					"duplicateNode": {
						"undo": function(params) {
							params.parent.removeChild(params.clone);
						},
						"redo": function(params) {
							params.parent.insertAfter(params.clone, params.node);
						}
					},
					"changeType": {
						"undo": function(params) {
							params.node.changeType(params.oldType);
						},
						"redo": function(params) {
							params.node.changeType(params.newType);
						}
					},
					"moveNode": {
						"undo": function(params) {
							params.startParent.moveTo(params.node, params.startIndex);
						},
						"redo": function(params) {
							params.endParent.moveTo(params.node, params.endIndex);
						}
					},
					"sort": {
						"undo": function(params) {
							var node = params.node;
							node.hideChilds();
							node.sort = params.oldSort;
							node.childs = params.oldChilds;
							node.showChilds();
						},
						"redo": function(params) {
							var node = params.node;
							node.hideChilds();
							node.sort = params.newSort;
							node.childs = params.newChilds;
							node.showChilds();
						}
					}
					// TODO: restore the original caret position and selection with each undo
					// TODO: implement history for actions "expand", "collapse", "scroll", "setDocument"
				};
			}
			History.prototype.onChange = function() {
			};
			History.prototype.add = function(action, params) {
				this.index++;
				this.history[this.index] = {
					"action": action,
					"params": params,
					"timestamp": /* @__PURE__ */ new Date()
				};
				if (this.index < this.history.length - 1) {
					this.history.splice(this.index + 1, this.history.length - this.index - 1);
				}
				this.onChange();
			};
			History.prototype.clear = function() {
				this.history = [];
				this.index = -1;
				this.onChange();
			};
			History.prototype.canUndo = function() {
				return this.index >= 0;
			};
			History.prototype.canRedo = function() {
				return this.index < this.history.length - 1;
			};
			History.prototype.undo = function() {
				if (this.canUndo()) {
					var obj = this.history[this.index];
					if (obj) {
						var action = this.actions[obj.action];
						if (action && action.undo) {
							action.undo(obj.params);
							if (obj.params.oldSelection) {
								this.editor.setSelection(obj.params.oldSelection);
							}
						} else {
							util.log('Error: unknown action "' + obj.action + '"');
						}
					}
					this.index--;
					this.onChange();
				}
			};
			History.prototype.redo = function() {
				if (this.canRedo()) {
					this.index++;
					var obj = this.history[this.index];
					if (obj) {
						var action = this.actions[obj.action];
						if (action && action.redo) {
							action.redo(obj.params);
							if (obj.params.newSelection) {
								this.editor.setSelection(obj.params.newSelection);
							}
						} else {
							util.log('Error: unknown action "' + obj.action + '"');
						}
					}
					this.onChange();
				}
			};
			module.exports = History;
		}
	});

	// src/js/SearchBox.js
	var require_SearchBox = __commonJS({
		"src/js/SearchBox.js"(exports, module) {
			function SearchBox(editor, container) {
				var searchBox = this;
				this.editor = editor;
				this.timeout = void 0;
				this.delay = 200;
				this.lastText = void 0;
				this.dom = {};
				this.dom.container = container;
				var table = document.createElement("table");
				this.dom.table = table;
				table.className = "search";
				container.appendChild(table);
				var tbody = document.createElement("tbody");
				this.dom.tbody = tbody;
				table.appendChild(tbody);
				var tr = document.createElement("tr");
				tbody.appendChild(tr);
				var td = document.createElement("td");
				tr.appendChild(td);
				var results = document.createElement("div");
				this.dom.results = results;
				results.className = "results";
				td.appendChild(results);
				td = document.createElement("td");
				tr.appendChild(td);
				var divInput = document.createElement("div");
				this.dom.input = divInput;
				divInput.className = "frame";
				divInput.title = "Search fields and values";
				td.appendChild(divInput);
				var tableInput = document.createElement("table");
				divInput.appendChild(tableInput);
				var tbodySearch = document.createElement("tbody");
				tableInput.appendChild(tbodySearch);
				tr = document.createElement("tr");
				tbodySearch.appendChild(tr);
				var refreshSearch = document.createElement("button");
				refreshSearch.className = "refresh";
				td = document.createElement("td");
				td.appendChild(refreshSearch);
				tr.appendChild(td);
				var search = document.createElement("input");
				this.dom.search = search;
				search.oninput = function(event) {
					searchBox._onDelayedSearch(event);
				};
				search.onchange = function(event) {
					searchBox._onSearch(event);
				};
				search.onkeydown = function(event) {
					searchBox._onKeyDown(event);
				};
				search.onkeyup = function(event) {
					searchBox._onKeyUp(event);
				};
				refreshSearch.onclick = function(event) {
					search.select();
				};
				td = document.createElement("td");
				td.appendChild(search);
				tr.appendChild(td);
				var searchNext = document.createElement("button");
				searchNext.title = "Next result (Enter)";
				searchNext.className = "next";
				searchNext.onclick = function() {
					searchBox.next();
				};
				td = document.createElement("td");
				td.appendChild(searchNext);
				tr.appendChild(td);
				var searchPrevious = document.createElement("button");
				searchPrevious.title = "Previous result (Shift+Enter)";
				searchPrevious.className = "previous";
				searchPrevious.onclick = function() {
					searchBox.previous();
				};
				td = document.createElement("td");
				td.appendChild(searchPrevious);
				tr.appendChild(td);
			}
			SearchBox.prototype.next = function(focus) {
				if (this.results != void 0) {
					var index = this.resultIndex != void 0 ? this.resultIndex + 1 : 0;
					if (index > this.results.length - 1) {
						index = 0;
					}
					this._setActiveResult(index, focus);
				}
			};
			SearchBox.prototype.previous = function(focus) {
				if (this.results != void 0) {
					var max = this.results.length - 1;
					var index = this.resultIndex != void 0 ? this.resultIndex - 1 : max;
					if (index < 0) {
						index = max;
					}
					this._setActiveResult(index, focus);
				}
			};
			SearchBox.prototype._setActiveResult = function(index, focus) {
				if (this.activeResult) {
					var prevNode = this.activeResult.node;
					var prevElem = this.activeResult.elem;
					if (prevElem == "field") {
						delete prevNode.searchFieldActive;
					} else {
						delete prevNode.searchValueActive;
					}
					prevNode.updateDom();
				}
				if (!this.results || !this.results[index]) {
					this.resultIndex = void 0;
					this.activeResult = void 0;
					return;
				}
				this.resultIndex = index;
				var node = this.results[this.resultIndex].node;
				var elem = this.results[this.resultIndex].elem;
				if (elem == "field") {
					node.searchFieldActive = true;
				} else {
					node.searchValueActive = true;
				}
				this.activeResult = this.results[this.resultIndex];
				node.updateDom();
				node.scrollTo(function() {
					if (focus) {
						node.focus(elem);
					}
				});
			};
			SearchBox.prototype._clearDelay = function() {
				if (this.timeout != void 0) {
					clearTimeout(this.timeout);
					delete this.timeout;
				}
			};
			SearchBox.prototype._onDelayedSearch = function(event) {
				this._clearDelay();
				var searchBox = this;
				this.timeout = setTimeout(
					function(event2) {
						searchBox._onSearch(event2);
					},
					this.delay
				);
			};
			SearchBox.prototype._onSearch = function(event, forceSearch) {
				this._clearDelay();
				var value = this.dom.search.value;
				var text = value.length > 0 ? value : void 0;
				if (text != this.lastText || forceSearch) {
					this.lastText = text;
					this.results = this.editor.search(text);
					this._setActiveResult(void 0);
					if (text != void 0) {
						var resultCount = this.results.length;
						switch (resultCount) {
							case 0:
								this.dom.results.innerHTML = "no&nbsp;results";
								break;
							case 1:
								this.dom.results.innerHTML = "1&nbsp;result";
								break;
							default:
								this.dom.results.innerHTML = resultCount + "&nbsp;results";
								break;
						}
					} else {
						this.dom.results.innerHTML = "";
					}
				}
			};
			SearchBox.prototype._onKeyDown = function(event) {
				var keynum = event.which;
				if (keynum == 27) {
					this.dom.search.value = "";
					this._onSearch(event);
					event.preventDefault();
					event.stopPropagation();
				} else if (keynum == 13) {
					if (event.ctrlKey) {
						this._onSearch(event, true);
					} else if (event.shiftKey) {
						this.previous();
					} else {
						this.next();
					}
					event.preventDefault();
					event.stopPropagation();
				}
			};
			SearchBox.prototype._onKeyUp = function(event) {
				var keynum = event.keyCode;
				if (keynum != 27 && keynum != 13) {
					this._onDelayedSearch(event);
				}
			};
			module.exports = SearchBox;
		}
	});

	// src/js/ContextMenu.js
	var require_ContextMenu = __commonJS({
		"src/js/ContextMenu.js"(exports, module) {
			var util = require_util();
			function ContextMenu(items, options) {
				this.dom = {};
				var me = this;
				var dom = this.dom;
				this.anchor = void 0;
				this.items = items;
				this.eventListeners = {};
				this.selection = void 0;
				this.visibleSubmenu = void 0;
				this.onClose = options ? options.close : void 0;
				var menu = document.createElement("div");
				menu.className = "jsoneditor-contextmenu";
				dom.menu = menu;
				var list = document.createElement("ul");
				list.className = "menu";
				menu.appendChild(list);
				dom.list = list;
				dom.items = [];
				var focusButton = document.createElement("button");
				dom.focusButton = focusButton;
				var li = document.createElement("li");
				li.style.overflow = "hidden";
				li.style.height = "0";
				li.appendChild(focusButton);
				list.appendChild(li);
				function createMenuItems(list2, domItems, items2) {
					items2.forEach(function(item) {
						if (item.type == "separator") {
							var separator = document.createElement("div");
							separator.className = "separator";
							li2 = document.createElement("li");
							li2.appendChild(separator);
							list2.appendChild(li2);
						} else {
							var domItem = {};
							var li2 = document.createElement("li");
							list2.appendChild(li2);
							var button = document.createElement("button");
							button.className = item.className;
							domItem.button = button;
							if (item.title) {
								button.title = item.title;
							}
							if (item.click) {
								button.onclick = function() {
									me.hide();
									item.click();
								};
							}
							li2.appendChild(button);
							if (item.submenu) {
								var divIcon = document.createElement("div");
								divIcon.className = "icon";
								button.appendChild(divIcon);
								button.appendChild(document.createTextNode(item.text));
								var buttonSubmenu;
								if (item.click) {
									button.className += " default";
									var buttonExpand = document.createElement("button");
									domItem.buttonExpand = buttonExpand;
									buttonExpand.className = "expand";
									buttonExpand.innerHTML = '<div class="expand"></div>';
									li2.appendChild(buttonExpand);
									if (item.submenuTitle) {
										buttonExpand.title = item.submenuTitle;
									}
									buttonSubmenu = buttonExpand;
								} else {
									var divExpand = document.createElement("div");
									divExpand.className = "expand";
									button.appendChild(divExpand);
									buttonSubmenu = button;
								}
								buttonSubmenu.onclick = function() {
									me._onExpandItem(domItem);
									buttonSubmenu.focus();
								};
								var domSubItems = [];
								domItem.subItems = domSubItems;
								var ul = document.createElement("ul");
								domItem.ul = ul;
								ul.className = "menu";
								ul.style.height = "0";
								li2.appendChild(ul);
								createMenuItems(ul, domSubItems, item.submenu);
							} else {
								button.innerHTML = '<div class="icon"></div>' + item.text;
							}
							domItems.push(domItem);
						}
					});
				}
				createMenuItems(list, this.dom.items, items);
				this.maxHeight = 0;
				items.forEach(function(item) {
					var height = (items.length + (item.submenu ? item.submenu.length : 0)) * 24;
					me.maxHeight = Math.max(me.maxHeight, height);
				});
			}
			ContextMenu.prototype._getVisibleButtons = function() {
				var buttons = [];
				var me = this;
				this.dom.items.forEach(function(item) {
					buttons.push(item.button);
					if (item.buttonExpand) {
						buttons.push(item.buttonExpand);
					}
					if (item.subItems && item == me.expandedItem) {
						item.subItems.forEach(function(subItem) {
							buttons.push(subItem.button);
							if (subItem.buttonExpand) {
								buttons.push(subItem.buttonExpand);
							}
						});
					}
				});
				return buttons;
			};
			ContextMenu.visibleMenu = void 0;
			ContextMenu.prototype.show = function(anchor) {
				this.hide();
				var windowHeight = window.innerHeight, windowScroll = window.pageYOffset || document.scrollTop || 0, windowBottom = windowHeight + windowScroll, anchorHeight = anchor.offsetHeight, menuHeight = this.maxHeight;
				var left = util.getAbsoluteLeft(anchor);
				var top = util.getAbsoluteTop(anchor);
				if (top + anchorHeight + menuHeight < windowBottom) {
					this.dom.menu.style.left = left + "px";
					this.dom.menu.style.top = top + anchorHeight + "px";
					this.dom.menu.style.bottom = "";
				} else {
					this.dom.menu.style.left = left + "px";
					this.dom.menu.style.top = "";
					this.dom.menu.style.bottom = windowHeight - top + "px";
				}
				document.body.appendChild(this.dom.menu);
				var me = this;
				var list = this.dom.list;
				this.eventListeners.mousedown = util.addEventListener(
					document,
					"mousedown",
					function(event) {
						var target = event.target;
						if (target != list && !me._isChildOf(target, list)) {
							me.hide();
							event.stopPropagation();
							event.preventDefault();
						}
					}
				);
				this.eventListeners.mousewheel = util.addEventListener(
					document,
					"mousewheel",
					function(event) {
						event.stopPropagation();
						event.preventDefault();
					}
				);
				this.eventListeners.keydown = util.addEventListener(
					document,
					"keydown",
					function(event) {
						me._onKeyDown(event);
					}
				);
				this.selection = util.getSelection();
				this.anchor = anchor;
				setTimeout(function() {
					me.dom.focusButton.focus();
				}, 0);
				if (ContextMenu.visibleMenu) {
					ContextMenu.visibleMenu.hide();
				}
				ContextMenu.visibleMenu = this;
			};
			ContextMenu.prototype.hide = function() {
				if (this.dom.menu.parentNode) {
					this.dom.menu.parentNode.removeChild(this.dom.menu);
					if (this.onClose) {
						this.onClose();
					}
				}
				for (var name in this.eventListeners) {
					if (this.eventListeners.hasOwnProperty(name)) {
						var fn = this.eventListeners[name];
						if (fn) {
							util.removeEventListener(document, name, fn);
						}
						delete this.eventListeners[name];
					}
				}
				if (ContextMenu.visibleMenu == this) {
					ContextMenu.visibleMenu = void 0;
				}
			};
			ContextMenu.prototype._onExpandItem = function(domItem) {
				var me = this;
				var alreadyVisible = domItem == this.expandedItem;
				var expandedItem = this.expandedItem;
				if (expandedItem) {
					expandedItem.ul.style.height = "0";
					expandedItem.ul.style.padding = "";
					setTimeout(function() {
						if (me.expandedItem != expandedItem) {
							expandedItem.ul.style.display = "";
							util.removeClassName(expandedItem.ul.parentNode, "selected");
						}
					}, 300);
					this.expandedItem = void 0;
				}
				if (!alreadyVisible) {
					var ul = domItem.ul;
					ul.style.display = "block";
					var height = ul.clientHeight;
					setTimeout(function() {
						if (me.expandedItem == domItem) {
							ul.style.height = ul.childNodes.length * 24 + "px";
							ul.style.padding = "5px 10px";
						}
					}, 0);
					util.addClassName(ul.parentNode, "selected");
					this.expandedItem = domItem;
				}
			};
			ContextMenu.prototype._onKeyDown = function(event) {
				var target = event.target;
				var keynum = event.which;
				var handled = false;
				var buttons, targetIndex, prevButton, nextButton;
				if (keynum == 27) {
					if (this.selection) {
						util.setSelection(this.selection);
					}
					if (this.anchor) {
						this.anchor.focus();
					}
					this.hide();
					handled = true;
				} else if (keynum == 9) {
					if (!event.shiftKey) {
						buttons = this._getVisibleButtons();
						targetIndex = buttons.indexOf(target);
						if (targetIndex == buttons.length - 1) {
							buttons[0].focus();
							handled = true;
						}
					} else {
						buttons = this._getVisibleButtons();
						targetIndex = buttons.indexOf(target);
						if (targetIndex == 0) {
							buttons[buttons.length - 1].focus();
							handled = true;
						}
					}
				} else if (keynum == 37) {
					if (target.className == "expand") {
						buttons = this._getVisibleButtons();
						targetIndex = buttons.indexOf(target);
						prevButton = buttons[targetIndex - 1];
						if (prevButton) {
							prevButton.focus();
						}
					}
					handled = true;
				} else if (keynum == 38) {
					buttons = this._getVisibleButtons();
					targetIndex = buttons.indexOf(target);
					prevButton = buttons[targetIndex - 1];
					if (prevButton && prevButton.className == "expand") {
						prevButton = buttons[targetIndex - 2];
					}
					if (!prevButton) {
						prevButton = buttons[buttons.length - 1];
					}
					if (prevButton) {
						prevButton.focus();
					}
					handled = true;
				} else if (keynum == 39) {
					buttons = this._getVisibleButtons();
					targetIndex = buttons.indexOf(target);
					nextButton = buttons[targetIndex + 1];
					if (nextButton && nextButton.className == "expand") {
						nextButton.focus();
					}
					handled = true;
				} else if (keynum == 40) {
					buttons = this._getVisibleButtons();
					targetIndex = buttons.indexOf(target);
					nextButton = buttons[targetIndex + 1];
					if (nextButton && nextButton.className == "expand") {
						nextButton = buttons[targetIndex + 2];
					}
					if (!nextButton) {
						nextButton = buttons[0];
					}
					if (nextButton) {
						nextButton.focus();
						handled = true;
					}
					handled = true;
				}
				if (handled) {
					event.stopPropagation();
					event.preventDefault();
				}
			};
			ContextMenu.prototype._isChildOf = function(child, parent) {
				var e = child.parentNode;
				while (e) {
					if (e == parent) {
						return true;
					}
					e = e.parentNode;
				}
				return false;
			};
			module.exports = ContextMenu;
		}
	});

	// src/js/appendNodeFactory.js
	var require_appendNodeFactory = __commonJS({
		"src/js/appendNodeFactory.js"(exports, module) {
			var util = require_util();
			var ContextMenu = require_ContextMenu();
			function appendNodeFactory(Node) {
				function AppendNode(editor) {
					this.editor = editor;
					this.dom = {};
				}
				AppendNode.prototype = new Node();
				AppendNode.prototype.getDom = function() {
					var dom = this.dom;
					if (dom.tr) {
						return dom.tr;
					}
					this._updateEditability();
					var trAppend = document.createElement("tr");
					trAppend.node = this;
					dom.tr = trAppend;
					if (this.editable.field) {
						dom.tdDrag = document.createElement("td");
						var tdMenu = document.createElement("td");
						dom.tdMenu = tdMenu;
						var menu = document.createElement("button");
						menu.className = "contextmenu";
						menu.title = "Click to open the actions menu (Ctrl+M)";
						dom.menu = menu;
						tdMenu.appendChild(dom.menu);
					}
					var tdAppend = document.createElement("td");
					var domText = document.createElement("div");
					domText.innerHTML = "(empty)";
					domText.className = "readonly";
					tdAppend.appendChild(domText);
					dom.td = tdAppend;
					dom.text = domText;
					this.updateDom();
					return trAppend;
				};
				AppendNode.prototype.updateDom = function() {
					var dom = this.dom;
					var tdAppend = dom.td;
					if (tdAppend) {
						tdAppend.style.paddingLeft = this.getLevel() * 24 + 26 + "px";
					}
					var domText = dom.text;
					if (domText) {
						domText.innerHTML = "(empty " + this.parent.type + ")";
					}
					var trAppend = dom.tr;
					if (!this.isVisible()) {
						if (dom.tr.firstChild) {
							if (dom.tdDrag) {
								trAppend.removeChild(dom.tdDrag);
							}
							if (dom.tdMenu) {
								trAppend.removeChild(dom.tdMenu);
							}
							trAppend.removeChild(tdAppend);
						}
					} else {
						if (!dom.tr.firstChild) {
							if (dom.tdDrag) {
								trAppend.appendChild(dom.tdDrag);
							}
							if (dom.tdMenu) {
								trAppend.appendChild(dom.tdMenu);
							}
							trAppend.appendChild(tdAppend);
						}
					}
				};
				AppendNode.prototype.isVisible = function() {
					return this.parent.childs.length == 0;
				};
				AppendNode.prototype.showContextMenu = function(anchor, onClose) {
					var node = this;
					var titles = Node.TYPE_TITLES;
					var items = [
						// create append button
						{
							"text": "Append",
							"title": "Append a new field with type 'auto' (Ctrl+Shift+Ins)",
							"submenuTitle": "Select the type of the field to be appended",
							"className": "insert",
							"click": function() {
								node._onAppend("", "", "auto");
							},
							"submenu": [
								{
									"text": "Auto",
									"className": "type-auto",
									"title": titles.auto,
									"click": function() {
										node._onAppend("", "", "auto");
									}
								},
								{
									"text": "Array",
									"className": "type-array",
									"title": titles.array,
									"click": function() {
										node._onAppend("", []);
									}
								},
								{
									"text": "Object",
									"className": "type-object",
									"title": titles.object,
									"click": function() {
										node._onAppend("", {});
									}
								},
								{
									"text": "String",
									"className": "type-string",
									"title": titles.string,
									"click": function() {
										node._onAppend("", "", "string");
									}
								}
							]
						}
					];
					var menu = new ContextMenu(items, { close: onClose });
					menu.show(anchor);
				};
				AppendNode.prototype.onEvent = function(event) {
					var type = event.type;
					var target = event.target || event.srcElement;
					var dom = this.dom;
					var menu = dom.menu;
					if (target == menu) {
						if (type == "mouseover") {
							this.editor.highlighter.highlight(this.parent);
						} else if (type == "mouseout") {
							this.editor.highlighter.unhighlight();
						}
					}
					if (type == "click" && target == dom.menu) {
						var highlighter = this.editor.highlighter;
						highlighter.highlight(this.parent);
						highlighter.lock();
						util.addClassName(dom.menu, "selected");
						this.showContextMenu(dom.menu, function() {
							util.removeClassName(dom.menu, "selected");
							highlighter.unlock();
							highlighter.unhighlight();
						});
					}
					if (type == "keydown") {
						this.onKeyDown(event);
					}
				};
				return AppendNode;
			}
			module.exports = appendNodeFactory;
		}
	});

	// src/js/Node.js
	var require_Node = __commonJS({
		"src/js/Node.js"(exports, module) {
			var ContextMenu = require_ContextMenu();
			var appendNodeFactory = require_appendNodeFactory();
			var util = require_util();
			function Node(editor, params) {
				this.editor = editor;
				this.dom = {};
				this.expanded = false;
				if (params && params instanceof Object) {
					this.setField(params.field, params.fieldEditable);
					this.setValue(params.value, params.type);
				} else {
					this.setField("");
					this.setValue(null);
				}
			}
			Node.prototype._updateEditability = function() {
				this.editable = {
					field: true,
					value: true
				};
				if (this.editor) {
					this.editable.field = this.editor.options.mode === "tree";
					this.editable.value = this.editor.options.mode !== "view";
					if (this.editor.options.mode === "tree" && typeof this.editor.options.editable === "function") {
						var editable = this.editor.options.editable({
							field: this.field,
							value: this.value,
							path: this.path()
						});
						if (typeof editable === "boolean") {
							this.editable.field = editable;
							this.editable.value = editable;
						} else {
							if (typeof editable.field === "boolean") this.editable.field = editable.field;
							if (typeof editable.value === "boolean") this.editable.value = editable.value;
						}
					}
				}
			};
			Node.prototype.path = function() {
				var node = this;
				var path = [];
				while (node) {
					var field = node.field != void 0 ? node.field : node.index;
					if (field !== void 0) {
						path.unshift(field);
					}
					node = node.parent;
				}
				return path;
			};
			Node.prototype.setParent = function(parent) {
				this.parent = parent;
			};
			Node.prototype.setField = function(field, fieldEditable) {
				this.field = field;
				this.fieldEditable = fieldEditable === true;
			};
			Node.prototype.getField = function() {
				if (this.field === void 0) {
					this._getDomField();
				}
				return this.field;
			};
			Node.prototype.setValue = function(value, type) {
				var childValue, child;
				var childs = this.childs;
				if (childs) {
					while (childs.length) {
						this.removeChild(childs[0]);
					}
				}
				this.type = this._getType(value);
				if (type && type != this.type) {
					if (type == "string" && this.type == "auto") {
						this.type = type;
					} else {
						throw new Error('Type mismatch: cannot cast value of type "' + this.type + ' to the specified type "' + type + '"');
					}
				}
				if (this.type == "array") {
					this.childs = [];
					for (var i = 0, iMax = value.length; i < iMax; i++) {
						childValue = value[i];
						if (childValue !== void 0 && !(childValue instanceof Function)) {
							child = new Node(this.editor, {
								value: childValue
							});
							this.appendChild(child);
						}
					}
					this.value = "";
				} else if (this.type == "object") {
					this.childs = [];
					for (var childField in value) {
						if (value.hasOwnProperty(childField)) {
							childValue = value[childField];
							if (childValue !== void 0 && !(childValue instanceof Function)) {
								child = new Node(this.editor, {
									field: childField,
									value: childValue
								});
								this.appendChild(child);
							}
						}
					}
					this.value = "";
				} else {
					this.childs = void 0;
					this.value = value;
				}
			};
			Node.prototype.getValue = function() {
				if (this.type == "array") {
					var arr = [];
					this.childs.forEach(function(child) {
						arr.push(child.getValue());
					});
					return arr;
				} else if (this.type == "object") {
					var obj = {};
					this.childs.forEach(function(child) {
						obj[child.getField()] = child.getValue();
					});
					return obj;
				} else {
					if (this.value === void 0) {
						this._getDomValue();
					}
					return this.value;
				}
			};
			Node.prototype.getLevel = function() {
				return this.parent ? this.parent.getLevel() + 1 : 0;
			};
			Node.prototype.clone = function() {
				var clone = new Node(this.editor);
				clone.type = this.type;
				clone.field = this.field;
				clone.fieldInnerText = this.fieldInnerText;
				clone.fieldEditable = this.fieldEditable;
				clone.value = this.value;
				clone.valueInnerText = this.valueInnerText;
				clone.expanded = this.expanded;
				if (this.childs) {
					var cloneChilds = [];
					this.childs.forEach(function(child) {
						var childClone = child.clone();
						childClone.setParent(clone);
						cloneChilds.push(childClone);
					});
					clone.childs = cloneChilds;
				} else {
					clone.childs = void 0;
				}
				return clone;
			};
			Node.prototype.expand = function(recurse) {
				if (!this.childs) {
					return;
				}
				this.expanded = true;
				if (this.dom.expand) {
					this.dom.expand.className = "expanded";
				}
				this.showChilds();
				if (recurse !== false) {
					this.childs.forEach(function(child) {
						child.expand(recurse);
					});
				}
			};
			Node.prototype.collapse = function(recurse) {
				if (!this.childs) {
					return;
				}
				this.hideChilds();
				if (recurse !== false) {
					this.childs.forEach(function(child) {
						child.collapse(recurse);
					});
				}
				if (this.dom.expand) {
					this.dom.expand.className = "collapsed";
				}
				this.expanded = false;
			};
			Node.prototype.showChilds = function() {
				var childs = this.childs;
				if (!childs) {
					return;
				}
				if (!this.expanded) {
					return;
				}
				var tr = this.dom.tr;
				var table = tr ? tr.parentNode : void 0;
				if (table) {
					var append = this.getAppend();
					var nextTr = tr.nextSibling;
					if (nextTr) {
						table.insertBefore(append, nextTr);
					} else {
						table.appendChild(append);
					}
					this.childs.forEach(function(child) {
						table.insertBefore(child.getDom(), append);
						child.showChilds();
					});
				}
			};
			Node.prototype.hide = function() {
				var tr = this.dom.tr;
				var table = tr ? tr.parentNode : void 0;
				if (table) {
					table.removeChild(tr);
				}
				this.hideChilds();
			};
			Node.prototype.hideChilds = function() {
				var childs = this.childs;
				if (!childs) {
					return;
				}
				if (!this.expanded) {
					return;
				}
				var append = this.getAppend();
				if (append.parentNode) {
					append.parentNode.removeChild(append);
				}
				this.childs.forEach(function(child) {
					child.hide();
				});
			};
			Node.prototype.appendChild = function(node) {
				if (this._hasChilds()) {
					node.setParent(this);
					node.fieldEditable = this.type == "object";
					if (this.type == "array") {
						node.index = this.childs.length;
					}
					this.childs.push(node);
					if (this.expanded) {
						var newTr = node.getDom();
						var appendTr = this.getAppend();
						var table = appendTr ? appendTr.parentNode : void 0;
						if (appendTr && table) {
							table.insertBefore(newTr, appendTr);
						}
						node.showChilds();
					}
					this.updateDom({ "updateIndexes": true });
					node.updateDom({ "recurse": true });
				}
			};
			Node.prototype.moveBefore = function(node, beforeNode) {
				if (this._hasChilds()) {
					var tbody = this.dom.tr ? this.dom.tr.parentNode : void 0;
					if (tbody) {
						var trTemp = document.createElement("tr");
						trTemp.style.height = tbody.clientHeight + "px";
						tbody.appendChild(trTemp);
					}
					if (node.parent) {
						node.parent.removeChild(node);
					}
					if (beforeNode instanceof AppendNode) {
						this.appendChild(node);
					} else {
						this.insertBefore(node, beforeNode);
					}
					if (tbody) {
						tbody.removeChild(trTemp);
					}
				}
			};
			Node.prototype.moveTo = function(node, index) {
				if (node.parent == this) {
					var currentIndex = this.childs.indexOf(node);
					if (currentIndex < index) {
						index++;
					}
				}
				var beforeNode = this.childs[index] || this.append;
				this.moveBefore(node, beforeNode);
			};
			Node.prototype.insertBefore = function(node, beforeNode) {
				if (this._hasChilds()) {
					if (beforeNode == this.append) {
						node.setParent(this);
						node.fieldEditable = this.type == "object";
						this.childs.push(node);
					} else {
						var index = this.childs.indexOf(beforeNode);
						if (index == -1) {
							throw new Error("Node not found");
						}
						node.setParent(this);
						node.fieldEditable = this.type == "object";
						this.childs.splice(index, 0, node);
					}
					if (this.expanded) {
						var newTr = node.getDom();
						var nextTr = beforeNode.getDom();
						var table = nextTr ? nextTr.parentNode : void 0;
						if (nextTr && table) {
							table.insertBefore(newTr, nextTr);
						}
						node.showChilds();
					}
					this.updateDom({ "updateIndexes": true });
					node.updateDom({ "recurse": true });
				}
			};
			Node.prototype.insertAfter = function(node, afterNode) {
				if (this._hasChilds()) {
					var index = this.childs.indexOf(afterNode);
					var beforeNode = this.childs[index + 1];
					if (beforeNode) {
						this.insertBefore(node, beforeNode);
					} else {
						this.appendChild(node);
					}
				}
			};
			Node.prototype.search = function(text) {
				var results = [];
				var index;
				var search = text ? text.toLowerCase() : void 0;
				delete this.searchField;
				delete this.searchValue;
				if (this.field != void 0) {
					var field = String(this.field).toLowerCase();
					index = field.indexOf(search);
					if (index != -1) {
						this.searchField = true;
						results.push({
							"node": this,
							"elem": "field"
						});
					}
					this._updateDomField();
				}
				if (this._hasChilds()) {
					if (this.childs) {
						var childResults = [];
						this.childs.forEach(function(child) {
							childResults = childResults.concat(child.search(text));
						});
						results = results.concat(childResults);
					}
					if (search != void 0) {
						var recurse = false;
						if (childResults.length == 0) {
							this.collapse(recurse);
						} else {
							this.expand(recurse);
						}
					}
				} else {
					if (this.value != void 0) {
						var value = String(this.value).toLowerCase();
						index = value.indexOf(search);
						if (index != -1) {
							this.searchValue = true;
							results.push({
								"node": this,
								"elem": "value"
							});
						}
					}
					this._updateDomValue();
				}
				return results;
			};
			Node.prototype.scrollTo = function(callback) {
				if (!this.dom.tr || !this.dom.tr.parentNode) {
					var parent = this.parent;
					var recurse = false;
					while (parent) {
						parent.expand(recurse);
						parent = parent.parent;
					}
				}
				if (this.dom.tr && this.dom.tr.parentNode) {
					this.editor.scrollTo(this.dom.tr.offsetTop, callback);
				}
			};
			Node.focusElement = void 0;
			Node.prototype.focus = function(elementName) {
				Node.focusElement = elementName;
				if (this.dom.tr && this.dom.tr.parentNode) {
					var dom = this.dom;
					switch (elementName) {
						case "drag":
							if (dom.drag) {
								dom.drag.focus();
							} else {
								dom.menu.focus();
							}
							break;
						case "menu":
							dom.menu.focus();
							break;
						case "expand":
							if (this._hasChilds()) {
								dom.expand.focus();
							} else if (dom.field && this.fieldEditable) {
								dom.field.focus();
								util.selectContentEditable(dom.field);
							} else if (dom.value && !this._hasChilds()) {
								dom.value.focus();
								util.selectContentEditable(dom.value);
							} else {
								dom.menu.focus();
							}
							break;
						case "field":
							if (dom.field && this.fieldEditable) {
								dom.field.focus();
								util.selectContentEditable(dom.field);
							} else if (dom.value && !this._hasChilds()) {
								dom.value.focus();
								util.selectContentEditable(dom.value);
							} else if (this._hasChilds()) {
								dom.expand.focus();
							} else {
								dom.menu.focus();
							}
							break;
						case "value":
						default:
							if (dom.value && !this._hasChilds()) {
								dom.value.focus();
								util.selectContentEditable(dom.value);
							} else if (dom.field && this.fieldEditable) {
								dom.field.focus();
								util.selectContentEditable(dom.field);
							} else if (this._hasChilds()) {
								dom.expand.focus();
							} else {
								dom.menu.focus();
							}
							break;
					}
				}
			};
			Node.select = function(editableDiv) {
				setTimeout(function() {
					util.selectContentEditable(editableDiv);
				}, 0);
			};
			Node.prototype.blur = function() {
				this._getDomValue(false);
				this._getDomField(false);
			};
			Node.prototype._duplicate = function(node) {
				var clone = node.clone();
				this.insertAfter(clone, node);
				return clone;
			};
			Node.prototype.containsNode = function(node) {
				if (this == node) {
					return true;
				}
				var childs = this.childs;
				if (childs) {
					for (var i = 0, iMax = childs.length; i < iMax; i++) {
						if (childs[i].containsNode(node)) {
							return true;
						}
					}
				}
				return false;
			};
			Node.prototype._move = function(node, beforeNode) {
				if (node == beforeNode) {
					return;
				}
				if (node.containsNode(this)) {
					throw new Error("Cannot move a field into a child of itself");
				}
				if (node.parent) {
					node.parent.removeChild(node);
				}
				var clone = node.clone();
				node.clearDom();
				if (beforeNode) {
					this.insertBefore(clone, beforeNode);
				} else {
					this.appendChild(clone);
				}
			};
			Node.prototype.removeChild = function(node) {
				if (this.childs) {
					var index = this.childs.indexOf(node);
					if (index != -1) {
						node.hide();
						delete node.searchField;
						delete node.searchValue;
						var removedNode = this.childs.splice(index, 1)[0];
						this.updateDom({ "updateIndexes": true });
						return removedNode;
					}
				}
				return void 0;
			};
			Node.prototype._remove = function(node) {
				this.removeChild(node);
			};
			Node.prototype.changeType = function(newType) {
				var oldType = this.type;
				if (oldType == newType) {
					return;
				}
				if ((newType == "string" || newType == "auto") && (oldType == "string" || oldType == "auto")) {
					this.type = newType;
				} else {
					var table = this.dom.tr ? this.dom.tr.parentNode : void 0;
					var lastTr;
					if (this.expanded) {
						lastTr = this.getAppend();
					} else {
						lastTr = this.getDom();
					}
					var nextTr = lastTr && lastTr.parentNode ? lastTr.nextSibling : void 0;
					this.hide();
					this.clearDom();
					this.type = newType;
					if (newType == "object") {
						if (!this.childs) {
							this.childs = [];
						}
						this.childs.forEach(function(child, index) {
							child.clearDom();
							delete child.index;
							child.fieldEditable = true;
							if (child.field == void 0) {
								child.field = "";
							}
						});
						if (oldType == "string" || oldType == "auto") {
							this.expanded = true;
						}
					} else if (newType == "array") {
						if (!this.childs) {
							this.childs = [];
						}
						this.childs.forEach(function(child, index) {
							child.clearDom();
							child.fieldEditable = false;
							child.index = index;
						});
						if (oldType == "string" || oldType == "auto") {
							this.expanded = true;
						}
					} else {
						this.expanded = false;
					}
					if (table) {
						if (nextTr) {
							table.insertBefore(this.getDom(), nextTr);
						} else {
							table.appendChild(this.getDom());
						}
					}
					this.showChilds();
				}
				if (newType == "auto" || newType == "string") {
					if (newType == "string") {
						this.value = String(this.value);
					} else {
						this.value = this._stringCast(String(this.value));
					}
					this.focus();
				}
				this.updateDom({ "updateIndexes": true });
			};
			Node.prototype._getDomValue = function(silent) {
				if (this.dom.value && this.type != "array" && this.type != "object") {
					this.valueInnerText = util.getInnerText(this.dom.value);
				}
				if (this.valueInnerText != void 0) {
					try {
						var value;
						if (this.type == "string") {
							value = this._unescapeHTML(this.valueInnerText);
						} else {
							var str = this._unescapeHTML(this.valueInnerText);
							value = this._stringCast(str);
						}
						if (value !== this.value) {
							var oldValue = this.value;
							this.value = value;
							this.editor._onAction("editValue", {
								"node": this,
								"oldValue": oldValue,
								"newValue": value,
								"oldSelection": this.editor.selection,
								"newSelection": this.editor.getSelection()
							});
						}
					} catch (err) {
						this.value = void 0;
						if (silent !== true) {
							throw err;
						}
					}
				}
			};
			Node.prototype._updateDomValue = function() {
				var domValue = this.dom.value;
				if (domValue) {
					var v = this.value;
					var t = this.type == "auto" ? util.type(v) : this.type;
					var isUrl = t == "string" && util.isUrl(v);
					var color = "";
					if (isUrl && !this.editable.value) {
						color = "";
					} else if (t == "string") {
						color = "green";
					} else if (t == "number") {
						color = "red";
					} else if (t == "boolean") {
						color = "darkorange";
					} else if (this._hasChilds()) {
						color = "";
					} else if (v === null) {
						color = "#004ED0";
					} else {
						color = "black";
					}
					domValue.style.color = color;
					var isEmpty = String(this.value) == "" && this.type != "array" && this.type != "object";
					if (isEmpty) {
						util.addClassName(domValue, "empty");
					} else {
						util.removeClassName(domValue, "empty");
					}
					if (isUrl) {
						util.addClassName(domValue, "url");
					} else {
						util.removeClassName(domValue, "url");
					}
					if (t == "array" || t == "object") {
						var count = this.childs ? this.childs.length : 0;
						domValue.title = this.type + " containing " + count + " items";
					} else if (t == "string" && util.isUrl(v)) {
						if (this.editable.value) {
							domValue.title = "Ctrl+Click or Ctrl+Enter to open url in new window";
						}
					} else {
						domValue.title = "";
					}
					if (this.searchValueActive) {
						util.addClassName(domValue, "highlight-active");
					} else {
						util.removeClassName(domValue, "highlight-active");
					}
					if (this.searchValue) {
						util.addClassName(domValue, "highlight");
					} else {
						util.removeClassName(domValue, "highlight");
					}
					util.stripFormatting(domValue);
				}
			};
			Node.prototype._updateDomField = function() {
				var domField = this.dom.field;
				if (domField) {
					var isEmpty = String(this.field) == "" && this.parent.type != "array";
					if (isEmpty) {
						util.addClassName(domField, "empty");
					} else {
						util.removeClassName(domField, "empty");
					}
					if (this.searchFieldActive) {
						util.addClassName(domField, "highlight-active");
					} else {
						util.removeClassName(domField, "highlight-active");
					}
					if (this.searchField) {
						util.addClassName(domField, "highlight");
					} else {
						util.removeClassName(domField, "highlight");
					}
					util.stripFormatting(domField);
				}
			};
			Node.prototype._getDomField = function(silent) {
				if (this.dom.field && this.fieldEditable) {
					this.fieldInnerText = util.getInnerText(this.dom.field);
				}
				if (this.fieldInnerText != void 0) {
					try {
						var field = this._unescapeHTML(this.fieldInnerText);
						if (field !== this.field) {
							var oldField = this.field;
							this.field = field;
							this.editor._onAction("editField", {
								"node": this,
								"oldValue": oldField,
								"newValue": field,
								"oldSelection": this.editor.selection,
								"newSelection": this.editor.getSelection()
							});
						}
					} catch (err) {
						this.field = void 0;
						if (silent !== true) {
							throw err;
						}
					}
				}
			};
			Node.prototype.clearDom = function() {
				this.dom = {};
			};
			Node.prototype.getDom = function() {
				var dom = this.dom;
				if (dom.tr) {
					return dom.tr;
				}
				this._updateEditability();
				dom.tr = document.createElement("tr");
				dom.tr.node = this;
				if (this.editor.options.mode === "tree") {
					var tdDrag = document.createElement("td");
					if (this.editable.field) {
						if (this.parent) {
							var domDrag = document.createElement("button");
							dom.drag = domDrag;
							domDrag.className = "dragarea";
							domDrag.title = "Drag to move this field (Alt+Shift+Arrows)";
							tdDrag.appendChild(domDrag);
						}
					}
					dom.tr.appendChild(tdDrag);
					var tdMenu = document.createElement("td");
					var menu = document.createElement("button");
					dom.menu = menu;
					menu.className = "contextmenu";
					menu.title = "Click to open the actions menu (Ctrl+M)";
					tdMenu.appendChild(dom.menu);
					dom.tr.appendChild(tdMenu);
				}
				var tdField = document.createElement("td");
				dom.tr.appendChild(tdField);
				dom.tree = this._createDomTree();
				tdField.appendChild(dom.tree);
				this.updateDom({ "updateIndexes": true });
				return dom.tr;
			};
			Node.prototype._onDragStart = function(event) {
				var node = this;
				if (!this.mousemove) {
					this.mousemove = util.addEventListener(
						document,
						"mousemove",
						function(event2) {
							node._onDrag(event2);
						}
					);
				}
				if (!this.mouseup) {
					this.mouseup = util.addEventListener(
						document,
						"mouseup",
						function(event2) {
							node._onDragEnd(event2);
						}
					);
				}
				this.editor.highlighter.lock();
				this.drag = {
					"oldCursor": document.body.style.cursor,
					"startParent": this.parent,
					"startIndex": this.parent.childs.indexOf(this),
					"mouseX": event.pageX,
					"level": this.getLevel()
				};
				document.body.style.cursor = "move";
				event.preventDefault();
			};
			Node.prototype._onDrag = function(event) {
				var mouseY = event.pageY;
				var mouseX = event.pageX;
				var trThis, trPrev, trNext, trFirst, trLast, trRoot;
				var nodePrev, nodeNext;
				var topThis, topPrev, topFirst, heightThis, bottomNext, heightNext;
				var moved = false;
				trThis = this.dom.tr;
				topThis = util.getAbsoluteTop(trThis);
				heightThis = trThis.offsetHeight;
				if (mouseY < topThis) {
					trPrev = trThis;
					do {
						trPrev = trPrev.previousSibling;
						nodePrev = Node.getNodeFromTarget(trPrev);
						topPrev = trPrev ? util.getAbsoluteTop(trPrev) : 0;
					} while (trPrev && mouseY < topPrev);
					if (nodePrev && !nodePrev.parent) {
						nodePrev = void 0;
					}
					if (!nodePrev) {
						trRoot = trThis.parentNode.firstChild;
						trPrev = trRoot ? trRoot.nextSibling : void 0;
						nodePrev = Node.getNodeFromTarget(trPrev);
						if (nodePrev == this) {
							nodePrev = void 0;
						}
					}
					if (nodePrev) {
						trPrev = nodePrev.dom.tr;
						topPrev = trPrev ? util.getAbsoluteTop(trPrev) : 0;
						if (mouseY > topPrev + heightThis) {
							nodePrev = void 0;
						}
					}
					if (nodePrev) {
						nodePrev.parent.moveBefore(this, nodePrev);
						moved = true;
					}
				} else {
					trLast = this.expanded && this.append ? this.append.getDom() : this.dom.tr;
					trFirst = trLast ? trLast.nextSibling : void 0;
					if (trFirst) {
						topFirst = util.getAbsoluteTop(trFirst);
						trNext = trFirst;
						do {
							nodeNext = Node.getNodeFromTarget(trNext);
							if (trNext) {
								bottomNext = trNext.nextSibling ? util.getAbsoluteTop(trNext.nextSibling) : 0;
								heightNext = trNext ? bottomNext - topFirst : 0;
								if (nodeNext.parent.childs.length == 1 && nodeNext.parent.childs[0] == this) {
									topThis += 24 - 1;
								}
							}
							trNext = trNext.nextSibling;
						} while (trNext && mouseY > topThis + heightNext);
						if (nodeNext && nodeNext.parent) {
							var diffX = mouseX - this.drag.mouseX;
							var diffLevel = Math.round(diffX / 24 / 2);
							var level = this.drag.level + diffLevel;
							var levelNext = nodeNext.getLevel();
							trPrev = nodeNext.dom.tr.previousSibling;
							while (levelNext < level && trPrev) {
								nodePrev = Node.getNodeFromTarget(trPrev);
								if (nodePrev == this || nodePrev._isChildOf(this)) {
								} else if (nodePrev instanceof AppendNode) {
									var childs = nodePrev.parent.childs;
									if (childs.length > 1 || childs.length == 1 && childs[0] != this) {
										nodeNext = Node.getNodeFromTarget(trPrev);
										levelNext = nodeNext.getLevel();
									} else {
										break;
									}
								} else {
									break;
								}
								trPrev = trPrev.previousSibling;
							}
							if (trLast.nextSibling != nodeNext.dom.tr) {
								nodeNext.parent.moveBefore(this, nodeNext);
								moved = true;
							}
						}
					}
				}
				if (moved) {
					this.drag.mouseX = mouseX;
					this.drag.level = this.getLevel();
				}
				this.editor.startAutoScroll(mouseY);
				event.preventDefault();
			};
			Node.prototype._onDragEnd = function(event) {
				var params = {
					"node": this,
					"startParent": this.drag.startParent,
					"startIndex": this.drag.startIndex,
					"endParent": this.parent,
					"endIndex": this.parent.childs.indexOf(this)
				};
				if (params.startParent != params.endParent || params.startIndex != params.endIndex) {
					this.editor._onAction("moveNode", params);
				}
				document.body.style.cursor = this.drag.oldCursor;
				this.editor.highlighter.unlock();
				delete this.drag;
				if (this.mousemove) {
					util.removeEventListener(document, "mousemove", this.mousemove);
					delete this.mousemove;
				}
				if (this.mouseup) {
					util.removeEventListener(document, "mouseup", this.mouseup);
					delete this.mouseup;
				}
				this.editor.stopAutoScroll();
				event.preventDefault();
			};
			Node.prototype._isChildOf = function(node) {
				var n = this.parent;
				while (n) {
					if (n == node) {
						return true;
					}
					n = n.parent;
				}
				return false;
			};
			Node.prototype._createDomField = function() {
				return document.createElement("div");
			};
			Node.prototype.setHighlight = function(highlight) {
				if (this.dom.tr) {
					this.dom.tr.className = highlight ? "highlight" : "";
					if (this.append) {
						this.append.setHighlight(highlight);
					}
					if (this.childs) {
						this.childs.forEach(function(child) {
							child.setHighlight(highlight);
						});
					}
				}
			};
			Node.prototype.updateValue = function(value) {
				this.value = value;
				this.updateDom();
			};
			Node.prototype.updateField = function(field) {
				this.field = field;
				this.updateDom();
			};
			Node.prototype.updateDom = function(options) {
				var domTree = this.dom.tree;
				if (domTree) {
					domTree.style.marginLeft = this.getLevel() * 24 + "px";
				}
				var domField = this.dom.field;
				if (domField) {
					if (this.fieldEditable) {
						domField.contentEditable = this.editable.field;
						domField.spellcheck = false;
						domField.className = "field";
					} else {
						domField.className = "readonly";
					}
					var field;
					if (this.index != void 0) {
						field = this.index;
					} else if (this.field != void 0) {
						field = this.field;
					} else if (this._hasChilds()) {
						field = this.type;
					} else {
						field = "";
					}
					domField.innerHTML = this._escapeHTML(field);
				}
				var domValue = this.dom.value;
				if (domValue) {
					var count = this.childs ? this.childs.length : 0;
					if (this.type == "array") {
						domValue.innerHTML = "[" + count + "]";
					} else if (this.type == "object") {
						domValue.innerHTML = "{" + count + "}";
					} else {
						domValue.innerHTML = this._escapeHTML(this.value);
					}
				}
				this._updateDomField();
				this._updateDomValue();
				if (options && options.updateIndexes === true) {
					this._updateDomIndexes();
				}
				if (options && options.recurse === true) {
					if (this.childs) {
						this.childs.forEach(function(child) {
							child.updateDom(options);
						});
					}
				}
				if (this.append) {
					this.append.updateDom();
				}
			};
			Node.prototype._updateDomIndexes = function() {
				var domValue = this.dom.value;
				var childs = this.childs;
				if (domValue && childs) {
					if (this.type == "array") {
						childs.forEach(function(child, index) {
							child.index = index;
							var childField = child.dom.field;
							if (childField) {
								childField.innerHTML = index;
							}
						});
					} else if (this.type == "object") {
						childs.forEach(function(child) {
							if (child.index != void 0) {
								delete child.index;
								if (child.field == void 0) {
									child.field = "";
								}
							}
						});
					}
				}
			};
			Node.prototype._createDomValue = function() {
				var domValue;
				if (this.type == "array") {
					domValue = document.createElement("div");
					domValue.className = "readonly";
					domValue.innerHTML = "[...]";
				} else if (this.type == "object") {
					domValue = document.createElement("div");
					domValue.className = "readonly";
					domValue.innerHTML = "{...}";
				} else {
					if (!this.editable.value && util.isUrl(this.value)) {
						domValue = document.createElement("a");
						domValue.className = "value";
						domValue.href = this.value;
						domValue.target = "_blank";
						domValue.innerHTML = this._escapeHTML(this.value);
					} else {
						domValue = document.createElement("div");
						domValue.contentEditable = this.editable.value;
						domValue.spellcheck = false;
						domValue.className = "value";
						domValue.innerHTML = this._escapeHTML(this.value);
					}
				}
				return domValue;
			};
			Node.prototype._createDomExpandButton = function() {
				var expand = document.createElement("button");
				if (this._hasChilds()) {
					expand.className = this.expanded ? "expanded" : "collapsed";
					expand.title = "Click to expand/collapse this field (Ctrl+E). \nCtrl+Click to expand/collapse including all childs.";
				} else {
					expand.className = "invisible";
					expand.title = "";
				}
				return expand;
			};
			Node.prototype._createDomTree = function() {
				var dom = this.dom;
				var domTree = document.createElement("table");
				var tbody = document.createElement("tbody");
				domTree.style.borderCollapse = "collapse";
				domTree.className = "values";
				domTree.appendChild(tbody);
				var tr = document.createElement("tr");
				tbody.appendChild(tr);
				var tdExpand = document.createElement("td");
				tdExpand.className = "tree";
				tr.appendChild(tdExpand);
				dom.expand = this._createDomExpandButton();
				tdExpand.appendChild(dom.expand);
				dom.tdExpand = tdExpand;
				var tdField = document.createElement("td");
				tdField.className = "tree";
				tr.appendChild(tdField);
				dom.field = this._createDomField();
				tdField.appendChild(dom.field);
				dom.tdField = tdField;
				var tdSeparator = document.createElement("td");
				tdSeparator.className = "tree";
				tr.appendChild(tdSeparator);
				if (this.type != "object" && this.type != "array") {
					tdSeparator.appendChild(document.createTextNode(":"));
					tdSeparator.className = "separator";
				}
				dom.tdSeparator = tdSeparator;
				var tdValue = document.createElement("td");
				tdValue.className = "tree";
				tr.appendChild(tdValue);
				dom.value = this._createDomValue();
				tdValue.appendChild(dom.value);
				dom.tdValue = tdValue;
				return domTree;
			};
			Node.prototype.onEvent = function(event) {
				var type = event.type, target = event.target || event.srcElement, dom = this.dom, node = this, focusNode, expandable = this._hasChilds();
				if (target == dom.drag || target == dom.menu) {
					if (type == "mouseover") {
						this.editor.highlighter.highlight(this);
					} else if (type == "mouseout") {
						this.editor.highlighter.unhighlight();
					}
				}
				if (type == "mousedown" && target == dom.drag) {
					this._onDragStart(event);
				}
				if (type == "click" && target == dom.menu) {
					var highlighter = node.editor.highlighter;
					highlighter.highlight(node);
					highlighter.lock();
					util.addClassName(dom.menu, "selected");
					this.showContextMenu(dom.menu, function() {
						util.removeClassName(dom.menu, "selected");
						highlighter.unlock();
						highlighter.unhighlight();
					});
				}
				if (type == "click" && target == dom.expand) {
					if (expandable) {
						var recurse = event.ctrlKey;
						this._onExpand(recurse);
					}
				}
				var domValue = dom.value;
				if (target == domValue) {
					switch (type) {
						case "focus":
							focusNode = this;
							break;
						case "blur":
						case "change":
							this._getDomValue(true);
							this._updateDomValue();
							if (this.value) {
								domValue.innerHTML = this._escapeHTML(this.value);
							}
							break;
						case "input":
							this._getDomValue(true);
							this._updateDomValue();
							break;
						case "keydown":
						case "mousedown":
							this.editor.selection = this.editor.getSelection();
							break;
						case "click":
							if (event.ctrlKey || !this.editable.value) {
								if (util.isUrl(this.value)) {
									window.open(this.value, "_blank");
								}
							}
							break;
						case "keyup":
							this._getDomValue(true);
							this._updateDomValue();
							break;
						case "cut":
						case "paste":
							setTimeout(function() {
								node._getDomValue(true);
								node._updateDomValue();
							}, 1);
							break;
					}
				}
				var domField = dom.field;
				if (target == domField) {
					switch (type) {
						case "focus":
							focusNode = this;
							break;
						case "blur":
						case "change":
							this._getDomField(true);
							this._updateDomField();
							if (this.field) {
								domField.innerHTML = this._escapeHTML(this.field);
							}
							break;
						case "input":
							this._getDomField(true);
							this._updateDomField();
							break;
						case "keydown":
						case "mousedown":
							this.editor.selection = this.editor.getSelection();
							break;
						case "keyup":
							this._getDomField(true);
							this._updateDomField();
							break;
						case "cut":
						case "paste":
							setTimeout(function() {
								node._getDomField(true);
								node._updateDomField();
							}, 1);
							break;
					}
				}
				var domTree = dom.tree;
				if (target == domTree.parentNode) {
					switch (type) {
						case "click":
							var left = event.offsetX != void 0 ? event.offsetX < (this.getLevel() + 1) * 24 : event.pageX < util.getAbsoluteLeft(dom.tdSeparator);
							if (left || expandable) {
								if (domField) {
									util.setEndOfContentEditable(domField);
									domField.focus();
								}
							} else {
								if (domValue) {
									util.setEndOfContentEditable(domValue);
									domValue.focus();
								}
							}
							break;
					}
				}
				if (target == dom.tdExpand && !expandable || target == dom.tdField || target == dom.tdSeparator) {
					switch (type) {
						case "click":
							if (domField) {
								util.setEndOfContentEditable(domField);
								domField.focus();
							}
							break;
					}
				}
				if (type == "keydown") {
					this.onKeyDown(event);
				}
			};
			Node.prototype.onKeyDown = function(event) {
				var keynum = event.which || event.keyCode;
				var target = event.target || event.srcElement;
				var ctrlKey = event.ctrlKey;
				var shiftKey = event.shiftKey;
				var altKey = event.altKey;
				var handled = false;
				var prevNode, nextNode, nextDom, nextDom2;
				var editable = this.editor.options.mode === "tree";
				if (keynum == 13) {
					if (target == this.dom.value) {
						if (!this.editable.value || event.ctrlKey) {
							if (util.isUrl(this.value)) {
								window.open(this.value, "_blank");
								handled = true;
							}
						}
					} else if (target == this.dom.expand) {
						var expandable = this._hasChilds();
						if (expandable) {
							var recurse = event.ctrlKey;
							this._onExpand(recurse);
							target.focus();
							handled = true;
						}
					}
				} else if (keynum == 68) {
					if (ctrlKey && editable) {
						this._onDuplicate();
						handled = true;
					}
				} else if (keynum == 69) {
					if (ctrlKey) {
						this._onExpand(shiftKey);
						target.focus();
						handled = true;
					}
				} else if (keynum == 77 && editable) {
					if (ctrlKey) {
						this.showContextMenu(target);
						handled = true;
					}
				} else if (keynum == 46 && editable) {
					if (ctrlKey) {
						this._onRemove();
						handled = true;
					}
				} else if (keynum == 45 && editable) {
					if (ctrlKey && !shiftKey) {
						this._onInsertBefore();
						handled = true;
					} else if (ctrlKey && shiftKey) {
						this._onInsertAfter();
						handled = true;
					}
				} else if (keynum == 35) {
					if (altKey) {
						var lastNode = this._lastNode();
						if (lastNode) {
							lastNode.focus(Node.focusElement || this._getElementName(target));
						}
						handled = true;
					}
				} else if (keynum == 36) {
					if (altKey) {
						var firstNode = this._firstNode();
						if (firstNode) {
							firstNode.focus(Node.focusElement || this._getElementName(target));
						}
						handled = true;
					}
				} else if (keynum == 37) {
					if (altKey && !shiftKey) {
						var prevElement = this._previousElement(target);
						if (prevElement) {
							this.focus(this._getElementName(prevElement));
						}
						handled = true;
					} else if (altKey && shiftKey && editable) {
						if (this.expanded) {
							var appendDom = this.getAppend();
							nextDom = appendDom ? appendDom.nextSibling : void 0;
						} else {
							var dom = this.getDom();
							nextDom = dom.nextSibling;
						}
						if (nextDom) {
							nextNode = Node.getNodeFromTarget(nextDom);
							nextDom2 = nextDom.nextSibling;
							nextNode2 = Node.getNodeFromTarget(nextDom2);
							if (nextNode && nextNode instanceof AppendNode && !(this.parent.childs.length == 1) && nextNode2 && nextNode2.parent) {
								nextNode2.parent.moveBefore(this, nextNode2);
								this.focus(Node.focusElement || this._getElementName(target));
							}
						}
					}
				} else if (keynum == 38) {
					if (altKey && !shiftKey) {
						prevNode = this._previousNode();
						if (prevNode) {
							prevNode.focus(Node.focusElement || this._getElementName(target));
						}
						handled = true;
					} else if (altKey && shiftKey) {
						prevNode = this._previousNode();
						if (prevNode && prevNode.parent) {
							prevNode.parent.moveBefore(this, prevNode);
							this.focus(Node.focusElement || this._getElementName(target));
						}
						handled = true;
					}
				} else if (keynum == 39) {
					if (altKey && !shiftKey) {
						var nextElement = this._nextElement(target);
						if (nextElement) {
							this.focus(this._getElementName(nextElement));
						}
						handled = true;
					} else if (altKey && shiftKey) {
						dom = this.getDom();
						var prevDom = dom.previousSibling;
						if (prevDom) {
							prevNode = Node.getNodeFromTarget(prevDom);
							if (prevNode && prevNode.parent && prevNode instanceof AppendNode && !prevNode.isVisible()) {
								prevNode.parent.moveBefore(this, prevNode);
								this.focus(Node.focusElement || this._getElementName(target));
							}
						}
					}
				} else if (keynum == 40) {
					if (altKey && !shiftKey) {
						nextNode = this._nextNode();
						if (nextNode) {
							nextNode.focus(Node.focusElement || this._getElementName(target));
						}
						handled = true;
					} else if (altKey && shiftKey && editable) {
						if (this.expanded) {
							nextNode = this.append ? this.append._nextNode() : void 0;
						} else {
							nextNode = this._nextNode();
						}
						nextDom = nextNode ? nextNode.getDom() : void 0;
						if (this.parent.childs.length == 1) {
							nextDom2 = nextDom;
						} else {
							nextDom2 = nextDom ? nextDom.nextSibling : void 0;
						}
						var nextNode2 = Node.getNodeFromTarget(nextDom2);
						if (nextNode2 && nextNode2.parent) {
							nextNode2.parent.moveBefore(this, nextNode2);
							this.focus(Node.focusElement || this._getElementName(target));
						}
						handled = true;
					}
				}
				if (handled) {
					event.preventDefault();
					event.stopPropagation();
				}
			};
			Node.prototype._onExpand = function(recurse) {
				if (recurse) {
					var table = this.dom.tr.parentNode;
					var frame = table.parentNode;
					var scrollTop = frame.scrollTop;
					frame.removeChild(table);
				}
				if (this.expanded) {
					this.collapse(recurse);
				} else {
					this.expand(recurse);
				}
				if (recurse) {
					frame.appendChild(table);
					frame.scrollTop = scrollTop;
				}
			};
			Node.prototype._onRemove = function() {
				this.editor.highlighter.unhighlight();
				var childs = this.parent.childs;
				var index = childs.indexOf(this);
				var oldSelection = this.editor.getSelection();
				if (childs[index + 1]) {
					childs[index + 1].focus();
				} else if (childs[index - 1]) {
					childs[index - 1].focus();
				} else {
					this.parent.focus();
				}
				var newSelection = this.editor.getSelection();
				this.parent._remove(this);
				this.editor._onAction("removeNode", {
					node: this,
					parent: this.parent,
					index,
					oldSelection,
					newSelection
				});
			};
			Node.prototype._onDuplicate = function() {
				var oldSelection = this.editor.getSelection();
				var clone = this.parent._duplicate(this);
				clone.focus();
				var newSelection = this.editor.getSelection();
				this.editor._onAction("duplicateNode", {
					node: this,
					clone,
					parent: this.parent,
					oldSelection,
					newSelection
				});
			};
			Node.prototype._onInsertBefore = function(field, value, type) {
				var oldSelection = this.editor.getSelection();
				var newNode = new Node(this.editor, {
					field: field != void 0 ? field : "",
					value: value != void 0 ? value : "",
					type
				});
				newNode.expand(true);
				this.parent.insertBefore(newNode, this);
				this.editor.highlighter.unhighlight();
				newNode.focus("field");
				var newSelection = this.editor.getSelection();
				this.editor._onAction("insertBeforeNode", {
					node: newNode,
					beforeNode: this,
					parent: this.parent,
					oldSelection,
					newSelection
				});
			};
			Node.prototype._onInsertAfter = function(field, value, type) {
				var oldSelection = this.editor.getSelection();
				var newNode = new Node(this.editor, {
					field: field != void 0 ? field : "",
					value: value != void 0 ? value : "",
					type
				});
				newNode.expand(true);
				this.parent.insertAfter(newNode, this);
				this.editor.highlighter.unhighlight();
				newNode.focus("field");
				var newSelection = this.editor.getSelection();
				this.editor._onAction("insertAfterNode", {
					node: newNode,
					afterNode: this,
					parent: this.parent,
					oldSelection,
					newSelection
				});
			};
			Node.prototype._onAppend = function(field, value, type) {
				var oldSelection = this.editor.getSelection();
				var newNode = new Node(this.editor, {
					field: field != void 0 ? field : "",
					value: value != void 0 ? value : "",
					type
				});
				newNode.expand(true);
				this.parent.appendChild(newNode);
				this.editor.highlighter.unhighlight();
				newNode.focus("field");
				var newSelection = this.editor.getSelection();
				this.editor._onAction("appendNode", {
					node: newNode,
					parent: this.parent,
					oldSelection,
					newSelection
				});
			};
			Node.prototype._onChangeType = function(newType) {
				var oldType = this.type;
				if (newType != oldType) {
					var oldSelection = this.editor.getSelection();
					this.changeType(newType);
					var newSelection = this.editor.getSelection();
					this.editor._onAction("changeType", {
						node: this,
						oldType,
						newType,
						oldSelection,
						newSelection
					});
				}
			};
			Node.prototype._onSort = function(direction) {
				if (this._hasChilds()) {
					var order = direction == "desc" ? -1 : 1;
					var prop = this.type == "array" ? "value" : "field";
					this.hideChilds();
					var oldChilds = this.childs;
					var oldSort = this.sort;
					this.childs = this.childs.concat();
					this.childs.sort(function(a, b) {
						if (a[prop] > b[prop]) return order;
						if (a[prop] < b[prop]) return -order;
						return 0;
					});
					this.sort = order == 1 ? "asc" : "desc";
					this.editor._onAction("sort", {
						node: this,
						oldChilds,
						oldSort,
						newChilds: this.childs,
						newSort: this.sort
					});
					this.showChilds();
				}
			};
			Node.prototype.getAppend = function() {
				if (!this.append) {
					this.append = new AppendNode(this.editor);
					this.append.setParent(this);
				}
				return this.append.getDom();
			};
			Node.getNodeFromTarget = function(target) {
				while (target) {
					if (target.node) {
						return target.node;
					}
					target = target.parentNode;
				}
				return void 0;
			};
			Node.prototype._previousNode = function() {
				var prevNode = null;
				var dom = this.getDom();
				if (dom && dom.parentNode) {
					var prevDom = dom;
					do {
						prevDom = prevDom.previousSibling;
						prevNode = Node.getNodeFromTarget(prevDom);
					} while (prevDom && (prevNode instanceof AppendNode && !prevNode.isVisible()));
				}
				return prevNode;
			};
			Node.prototype._nextNode = function() {
				var nextNode = null;
				var dom = this.getDom();
				if (dom && dom.parentNode) {
					var nextDom = dom;
					do {
						nextDom = nextDom.nextSibling;
						nextNode = Node.getNodeFromTarget(nextDom);
					} while (nextDom && (nextNode instanceof AppendNode && !nextNode.isVisible()));
				}
				return nextNode;
			};
			Node.prototype._firstNode = function() {
				var firstNode = null;
				var dom = this.getDom();
				if (dom && dom.parentNode) {
					var firstDom = dom.parentNode.firstChild;
					firstNode = Node.getNodeFromTarget(firstDom);
				}
				return firstNode;
			};
			Node.prototype._lastNode = function() {
				var lastNode = null;
				var dom = this.getDom();
				if (dom && dom.parentNode) {
					var lastDom = dom.parentNode.lastChild;
					lastNode = Node.getNodeFromTarget(lastDom);
					while (lastDom && (lastNode instanceof AppendNode && !lastNode.isVisible())) {
						lastDom = lastDom.previousSibling;
						lastNode = Node.getNodeFromTarget(lastDom);
					}
				}
				return lastNode;
			};
			Node.prototype._previousElement = function(elem) {
				var dom = this.dom;
				switch (elem) {
					case dom.value:
						if (this.fieldEditable) {
							return dom.field;
						}
					// intentional fall through
					case dom.field:
						if (this._hasChilds()) {
							return dom.expand;
						}
					// intentional fall through
					case dom.expand:
						return dom.menu;
					case dom.menu:
						if (dom.drag) {
							return dom.drag;
						}
					// intentional fall through
					default:
						return null;
				}
			};
			Node.prototype._nextElement = function(elem) {
				var dom = this.dom;
				switch (elem) {
					case dom.drag:
						return dom.menu;
					case dom.menu:
						if (this._hasChilds()) {
							return dom.expand;
						}
					// intentional fall through
					case dom.expand:
						if (this.fieldEditable) {
							return dom.field;
						}
					// intentional fall through
					case dom.field:
						if (!this._hasChilds()) {
							return dom.value;
						}
					default:
						return null;
				}
			};
			Node.prototype._getElementName = function(element) {
				var dom = this.dom;
				for (var name in dom) {
					if (dom.hasOwnProperty(name)) {
						if (dom[name] == element) {
							return name;
						}
					}
				}
				return null;
			};
			Node.prototype._hasChilds = function() {
				return this.type == "array" || this.type == "object";
			};
			Node.TYPE_TITLES = {
				"auto": 'Field type "auto". The field type is automatically determined from the value and can be a string, number, boolean, or null.',
				"object": 'Field type "object". An object contains an unordered set of key/value pairs.',
				"array": 'Field type "array". An array contains an ordered collection of values.',
				"string": 'Field type "string". Field type is not determined from the value, but always returned as string.'
			};
			Node.prototype.showContextMenu = function(anchor, onClose) {
				var node = this;
				var titles = Node.TYPE_TITLES;
				var items = [];
				if (this.editable.value) {
					items.push({
						text: "Type",
						title: "Change the type of this field",
						className: "type-" + this.type,
						submenu: [
							{
								text: "Auto",
								className: "type-auto" + (this.type == "auto" ? " selected" : ""),
								title: titles.auto,
								click: function() {
									node._onChangeType("auto");
								}
							},
							{
								text: "Array",
								className: "type-array" + (this.type == "array" ? " selected" : ""),
								title: titles.array,
								click: function() {
									node._onChangeType("array");
								}
							},
							{
								text: "Object",
								className: "type-object" + (this.type == "object" ? " selected" : ""),
								title: titles.object,
								click: function() {
									node._onChangeType("object");
								}
							},
							{
								text: "String",
								className: "type-string" + (this.type == "string" ? " selected" : ""),
								title: titles.string,
								click: function() {
									node._onChangeType("string");
								}
							}
						]
					});
				}
				if (this._hasChilds()) {
					var direction = this.sort == "asc" ? "desc" : "asc";
					items.push({
						text: "Sort",
						title: "Sort the childs of this " + this.type,
						className: "sort-" + direction,
						click: function() {
							node._onSort(direction);
						},
						submenu: [
							{
								text: "Ascending",
								className: "sort-asc",
								title: "Sort the childs of this " + this.type + " in ascending order",
								click: function() {
									node._onSort("asc");
								}
							},
							{
								text: "Descending",
								className: "sort-desc",
								title: "Sort the childs of this " + this.type + " in descending order",
								click: function() {
									node._onSort("desc");
								}
							}
						]
					});
				}
				if (this.parent && this.parent._hasChilds()) {
					if (items.length) {
						items.push({
							"type": "separator"
						});
					}
					var childs = node.parent.childs;
					if (node == childs[childs.length - 1]) {
						items.push({
							text: "Append",
							title: "Append a new field with type 'auto' after this field (Ctrl+Shift+Ins)",
							submenuTitle: "Select the type of the field to be appended",
							className: "append",
							click: function() {
								node._onAppend("", "", "auto");
							},
							submenu: [
								{
									text: "Auto",
									className: "type-auto",
									title: titles.auto,
									click: function() {
										node._onAppend("", "", "auto");
									}
								},
								{
									text: "Array",
									className: "type-array",
									title: titles.array,
									click: function() {
										node._onAppend("", []);
									}
								},
								{
									text: "Object",
									className: "type-object",
									title: titles.object,
									click: function() {
										node._onAppend("", {});
									}
								},
								{
									text: "String",
									className: "type-string",
									title: titles.string,
									click: function() {
										node._onAppend("", "", "string");
									}
								}
							]
						});
					}
					items.push({
						text: "Insert",
						title: "Insert a new field with type 'auto' before this field (Ctrl+Ins)",
						submenuTitle: "Select the type of the field to be inserted",
						className: "insert",
						click: function() {
							node._onInsertBefore("", "", "auto");
						},
						submenu: [
							{
								text: "Auto",
								className: "type-auto",
								title: titles.auto,
								click: function() {
									node._onInsertBefore("", "", "auto");
								}
							},
							{
								text: "Array",
								className: "type-array",
								title: titles.array,
								click: function() {
									node._onInsertBefore("", []);
								}
							},
							{
								text: "Object",
								className: "type-object",
								title: titles.object,
								click: function() {
									node._onInsertBefore("", {});
								}
							},
							{
								text: "String",
								className: "type-string",
								title: titles.string,
								click: function() {
									node._onInsertBefore("", "", "string");
								}
							}
						]
					});
					if (this.editable.field) {
						items.push({
							text: "Duplicate",
							title: "Duplicate this field (Ctrl+D)",
							className: "duplicate",
							click: function() {
								node._onDuplicate();
							}
						});
						items.push({
							text: "Remove",
							title: "Remove this field (Ctrl+Del)",
							className: "remove",
							click: function() {
								node._onRemove();
							}
						});
					}
				}
				var menu = new ContextMenu(items, { close: onClose });
				menu.show(anchor);
			};
			Node.prototype._getType = function(value) {
				if (value instanceof Array) {
					return "array";
				}
				if (value instanceof Object) {
					return "object";
				}
				if (typeof value == "string" && typeof this._stringCast(value) != "string") {
					return "string";
				}
				return "auto";
			};
			Node.prototype._stringCast = function(str) {
				var lower = str.toLowerCase(), num = Number(str), numFloat = parseFloat(str);
				if (str == "") {
					return "";
				} else if (lower == "null") {
					return null;
				} else if (lower == "true") {
					return true;
				} else if (lower == "false") {
					return false;
				} else if (!isNaN(num) && !isNaN(numFloat)) {
					return num;
				} else {
					return str;
				}
			};
			Node.prototype._escapeHTML = function(text) {
				var htmlEscaped = String(text).replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/  /g, " &nbsp;").replace(/^ /, "&nbsp;").replace(/ $/, "&nbsp;");
				var json = JSON.stringify(htmlEscaped);
				return json.substring(1, json.length - 1);
			};
			Node.prototype._unescapeHTML = function(escapedText) {
				var json = '"' + this._escapeJSON(escapedText) + '"';
				var htmlEscaped = util.parse(json);
				return htmlEscaped.replace(/&lt;/g, "<").replace(/&gt;/g, ">").replace(/&nbsp;|\u00A0/g, " ");
			};
			Node.prototype._escapeJSON = function(text) {
				var escaped = "";
				var i = 0, iMax = text.length;
				while (i < iMax) {
					var c = text.charAt(i);
					if (c == "\n") {
						escaped += "\\n";
					} else if (c == "\\") {
						escaped += c;
						i++;
						c = text.charAt(i);
						if ('"\\/bfnrtu'.indexOf(c) == -1) {
							escaped += "\\";
						}
						escaped += c;
					} else if (c == '"') {
						escaped += '\\"';
					} else {
						escaped += c;
					}
					i++;
				}
				return escaped;
			};
			var AppendNode = appendNodeFactory(Node);
			module.exports = Node;
		}
	});

	// src/js/modeswitcher.js
	var require_modeswitcher = __commonJS({
		"src/js/modeswitcher.js"(exports) {
			var ContextMenu = require_ContextMenu();
			function createModeSwitcher(editor, modes, current) {
				function switchMode(mode2) {
					editor.setMode(mode2);
					var modeBox = editor.dom && editor.dom.modeBox;
					if (modeBox) {
						modeBox.focus();
					}
				}
				var availableModes = {
					code: {
						"text": "Code",
						"title": "Switch to code highlighter",
						"click": function() {
							switchMode("code");
						}
					},
					form: {
						"text": "Form",
						"title": "Switch to form editor",
						"click": function() {
							switchMode("form");
						}
					},
					text: {
						"text": "Text",
						"title": "Switch to plain text editor",
						"click": function() {
							switchMode("text");
						}
					},
					tree: {
						"text": "Tree",
						"title": "Switch to tree editor",
						"click": function() {
							switchMode("tree");
						}
					},
					view: {
						"text": "View",
						"title": "Switch to tree view",
						"click": function() {
							switchMode("view");
						}
					}
				};
				var items = [];
				for (var i = 0; i < modes.length; i++) {
					var mode = modes[i];
					var item = availableModes[mode];
					if (!item) {
						throw new Error('Unknown mode "' + mode + '"');
					}
					item.className = "type-modes" + (current == mode ? " selected" : "");
					items.push(item);
				}
				var currentMode = availableModes[current];
				if (!currentMode) {
					throw new Error('Unknown mode "' + current + '"');
				}
				var currentTitle = currentMode.text;
				var box = document.createElement("button");
				box.className = "modes separator";
				box.innerHTML = currentTitle + " &#x25BE;";
				box.title = "Switch editor mode";
				box.onclick = function() {
					var menu = new ContextMenu(items);
					menu.show(box);
				};
				return box;
			}
			exports.create = createModeSwitcher;
		}
	});

	// src/js/treemode.js
	var require_treemode = __commonJS({
		"src/js/treemode.js"(exports, module) {
			var Highlighter = require_Highlighter();
			var History = require_History();
			var SearchBox = require_SearchBox();
			var Node = require_Node();
			var modeswitcher = require_modeswitcher();
			var util = require_util();
			var treemode = {};
			treemode.create = function(container, options) {
				if (!container) {
					throw new Error("No container element provided.");
				}
				this.container = container;
				this.dom = {};
				this.highlighter = new Highlighter();
				this.selection = void 0;
				this._setOptions(options);
				if (this.options.history && this.options.mode !== "view") {
					this.history = new History(this);
				}
				this._createFrame();
				this._createTable();
			};
			treemode._delete = function() {
				if (this.frame && this.container && this.frame.parentNode == this.container) {
					this.container.removeChild(this.frame);
				}
			};
			treemode._setOptions = function(options) {
				this.options = {
					search: true,
					history: true,
					mode: "tree",
					name: void 0
					// field name of root node
				};
				if (options) {
					for (var prop in options) {
						if (options.hasOwnProperty(prop)) {
							this.options[prop] = options[prop];
						}
					}
				}
			};
			var focusNode = void 0;
			var domFocus = null;
			treemode.set = function(json, name) {
				if (name) {
					util.log('Warning: second parameter "name" is deprecated. Use setName(name) instead.');
					this.options.name = name;
				}
				if (json instanceof Function || json === void 0) {
					this.clear();
				} else {
					this.content.removeChild(this.table);
					var params = {
						"field": this.options.name,
						"value": json
					};
					var node = new Node(this, params);
					this._setRoot(node);
					var recurse = false;
					this.node.expand(recurse);
					this.content.appendChild(this.table);
				}
				if (this.history) {
					this.history.clear();
				}
			};
			treemode.get = function() {
				if (focusNode) {
					focusNode.blur();
				}
				if (this.node) {
					return this.node.getValue();
				} else {
					return void 0;
				}
			};
			treemode.getText = function() {
				return JSON.stringify(this.get());
			};
			treemode.setText = function(jsonText) {
				this.set(util.parse(jsonText));
			};
			treemode.setName = function(name) {
				this.options.name = name;
				if (this.node) {
					this.node.updateField(this.options.name);
				}
			};
			treemode.getName = function() {
				return this.options.name;
			};
			treemode.focus = function() {
				var input = this.content.querySelector("[contenteditable=true]");
				if (input) {
					input.focus();
				} else if (this.node.dom.expand) {
					this.node.dom.expand.focus();
				} else if (this.node.dom.menu) {
					this.node.dom.menu.focus();
				} else {
					input = this.frame.querySelector("button");
					if (input) {
						input.focus();
					}
				}
			};
			treemode.clear = function() {
				if (this.node) {
					this.node.collapse();
					this.tbody.removeChild(this.node.getDom());
					delete this.node;
				}
			};
			treemode._setRoot = function(node) {
				this.clear();
				this.node = node;
				this.tbody.appendChild(node.getDom());
			};
			treemode.search = function(text) {
				var results;
				if (this.node) {
					this.content.removeChild(this.table);
					results = this.node.search(text);
					this.content.appendChild(this.table);
				} else {
					results = [];
				}
				return results;
			};
			treemode.expandAll = function() {
				if (this.node) {
					this.content.removeChild(this.table);
					this.node.expand();
					this.content.appendChild(this.table);
				}
			};
			treemode.collapseAll = function() {
				if (this.node) {
					this.content.removeChild(this.table);
					this.node.collapse();
					this.content.appendChild(this.table);
				}
			};
			treemode._onAction = function(action, params) {
				if (this.history) {
					this.history.add(action, params);
				}
				if (this.options.change) {
					try {
						this.options.change();
					} catch (err) {
						util.log("Error in change callback: ", err);
					}
				}
			};
			treemode.startAutoScroll = function(mouseY) {
				var me = this;
				var content = this.content;
				var top = util.getAbsoluteTop(content);
				var height = content.clientHeight;
				var bottom = top + height;
				var margin = 24;
				var interval = 50;
				if (mouseY < top + margin && content.scrollTop > 0) {
					this.autoScrollStep = (top + margin - mouseY) / 3;
				} else if (mouseY > bottom - margin && height + content.scrollTop < content.scrollHeight) {
					this.autoScrollStep = (bottom - margin - mouseY) / 3;
				} else {
					this.autoScrollStep = void 0;
				}
				if (this.autoScrollStep) {
					if (!this.autoScrollTimer) {
						this.autoScrollTimer = setInterval(function() {
							if (me.autoScrollStep) {
								content.scrollTop -= me.autoScrollStep;
							} else {
								me.stopAutoScroll();
							}
						}, interval);
					}
				} else {
					this.stopAutoScroll();
				}
			};
			treemode.stopAutoScroll = function() {
				if (this.autoScrollTimer) {
					clearTimeout(this.autoScrollTimer);
					delete this.autoScrollTimer;
				}
				if (this.autoScrollStep) {
					delete this.autoScrollStep;
				}
			};
			treemode.setSelection = function(selection) {
				if (!selection) {
					return;
				}
				if ("scrollTop" in selection && this.content) {
					this.content.scrollTop = selection.scrollTop;
				}
				if (selection.range) {
					util.setSelectionOffset(selection.range);
				}
				if (selection.dom) {
					selection.dom.focus();
				}
			};
			treemode.getSelection = function() {
				return {
					dom: domFocus,
					scrollTop: this.content ? this.content.scrollTop : 0,
					range: util.getSelectionOffset()
				};
			};
			treemode.scrollTo = function(top, callback) {
				var content = this.content;
				if (content) {
					var editor = this;
					if (editor.animateTimeout) {
						clearTimeout(editor.animateTimeout);
						delete editor.animateTimeout;
					}
					if (editor.animateCallback) {
						editor.animateCallback(false);
						delete editor.animateCallback;
					}
					var height = content.clientHeight;
					var bottom = content.scrollHeight - height;
					var finalScrollTop = Math.min(Math.max(top - height / 4, 0), bottom);
					var animate = function() {
						var scrollTop = content.scrollTop;
						var diff = finalScrollTop - scrollTop;
						if (Math.abs(diff) > 3) {
							content.scrollTop += diff / 3;
							editor.animateCallback = callback;
							editor.animateTimeout = setTimeout(animate, 50);
						} else {
							if (callback) {
								callback(true);
							}
							content.scrollTop = finalScrollTop;
							delete editor.animateTimeout;
							delete editor.animateCallback;
						}
					};
					animate();
				} else {
					if (callback) {
						callback(false);
					}
				}
			};
			treemode._createFrame = function() {
				this.frame = document.createElement("div");
				this.frame.className = "jsoneditor";
				this.container.appendChild(this.frame);
				var editor = this;
				function onEvent(event) {
					editor._onEvent(event);
				}
				this.frame.onclick = function(event) {
					var target = event.target;
					onEvent(event);
					if (target.nodeName == "BUTTON") {
						event.preventDefault();
					}
				};
				this.frame.oninput = onEvent;
				this.frame.onchange = onEvent;
				this.frame.onkeydown = onEvent;
				this.frame.onkeyup = onEvent;
				this.frame.oncut = onEvent;
				this.frame.onpaste = onEvent;
				this.frame.onmousedown = onEvent;
				this.frame.onmouseup = onEvent;
				this.frame.onmouseover = onEvent;
				this.frame.onmouseout = onEvent;
				util.addEventListener(this.frame, "focus", onEvent, true);
				util.addEventListener(this.frame, "blur", onEvent, true);
				this.frame.onfocusin = onEvent;
				this.frame.onfocusout = onEvent;
				this.menu = document.createElement("div");
				this.menu.className = "menu";
				this.frame.appendChild(this.menu);
				var expandAll = document.createElement("button");
				expandAll.className = "expand-all";
				expandAll.title = "Expand all fields";
				expandAll.onclick = function() {
					editor.expandAll();
				};
				this.menu.appendChild(expandAll);
				var collapseAll = document.createElement("button");
				collapseAll.title = "Collapse all fields";
				collapseAll.className = "collapse-all";
				collapseAll.onclick = function() {
					editor.collapseAll();
				};
				this.menu.appendChild(collapseAll);
				if (this.history) {
					var undo = document.createElement("button");
					undo.className = "undo separator";
					undo.title = "Undo last action (Ctrl+Z)";
					undo.onclick = function() {
						editor._onUndo();
					};
					this.menu.appendChild(undo);
					this.dom.undo = undo;
					var redo = document.createElement("button");
					redo.className = "redo";
					redo.title = "Redo (Ctrl+Shift+Z)";
					redo.onclick = function() {
						editor._onRedo();
					};
					this.menu.appendChild(redo);
					this.dom.redo = redo;
					this.history.onChange = function() {
						undo.disabled = !editor.history.canUndo();
						redo.disabled = !editor.history.canRedo();
					};
					this.history.onChange();
				}
				if (this.options && this.options.modes && this.options.modes.length) {
					var modeBox = modeswitcher.create(this, this.options.modes, this.options.mode);
					this.menu.appendChild(modeBox);
					this.dom.modeBox = modeBox;
				}
				if (this.options.search) {
					this.searchBox = new SearchBox(this, this.menu);
				}
			};
			treemode._onUndo = function() {
				if (this.history) {
					this.history.undo();
					if (this.options.change) {
						this.options.change();
					}
				}
			};
			treemode._onRedo = function() {
				if (this.history) {
					this.history.redo();
					if (this.options.change) {
						this.options.change();
					}
				}
			};
			treemode._onEvent = function(event) {
				var target = event.target;
				if (event.type == "keydown") {
					this._onKeyDown(event);
				}
				if (event.type == "focus") {
					domFocus = target;
				}
				var node = Node.getNodeFromTarget(target);
				if (node) {
					node.onEvent(event);
				}
			};
			treemode._onKeyDown = function(event) {
				var keynum = event.which || event.keyCode;
				var ctrlKey = event.ctrlKey;
				var shiftKey = event.shiftKey;
				var handled = false;
				if (keynum == 9) {
					setTimeout(function() {
						util.selectContentEditable(domFocus);
					}, 0);
				}
				if (this.searchBox) {
					if (ctrlKey && keynum == 70) {
						this.searchBox.dom.search.focus();
						this.searchBox.dom.search.select();
						handled = true;
					} else if (keynum == 114 || ctrlKey && keynum == 71) {
						var focus = true;
						if (!shiftKey) {
							this.searchBox.next(focus);
						} else {
							this.searchBox.previous(focus);
						}
						handled = true;
					}
				}
				if (this.history) {
					if (ctrlKey && !shiftKey && keynum == 90) {
						this._onUndo();
						handled = true;
					} else if (ctrlKey && shiftKey && keynum == 90) {
						this._onRedo();
						handled = true;
					}
				}
				if (handled) {
					event.preventDefault();
					event.stopPropagation();
				}
			};
			treemode._createTable = function() {
				var contentOuter = document.createElement("div");
				contentOuter.className = "outer";
				this.contentOuter = contentOuter;
				this.content = document.createElement("div");
				this.content.className = "tree";
				contentOuter.appendChild(this.content);
				this.table = document.createElement("table");
				this.table.className = "tree";
				this.content.appendChild(this.table);
				var col;
				this.colgroupContent = document.createElement("colgroup");
				if (this.options.mode === "tree") {
					col = document.createElement("col");
					col.width = "24px";
					this.colgroupContent.appendChild(col);
				}
				col = document.createElement("col");
				col.width = "24px";
				this.colgroupContent.appendChild(col);
				col = document.createElement("col");
				this.colgroupContent.appendChild(col);
				this.table.appendChild(this.colgroupContent);
				this.tbody = document.createElement("tbody");
				this.table.appendChild(this.tbody);
				this.frame.appendChild(contentOuter);
			};
			module.exports = [
				{
					mode: "tree",
					mixin: treemode,
					data: "json"
				},
				{
					mode: "view",
					mixin: treemode,
					data: "json"
				},
				{
					mode: "form",
					mixin: treemode,
					data: "json"
				}
			];
		}
	});

	// src/js/textmode.js
	var require_textmode = __commonJS({
		"src/js/textmode.js"(exports, module) {
			var ace;
			try {
				ace = __require("./ace");
			} catch (err) {
			}
			var modeswitcher = require_modeswitcher();
			var util = require_util();
			var textmode = {};
			textmode.create = function(container, options) {
				options = options || {};
				this.options = options;
				if (options.indentation) {
					this.indentation = Number(options.indentation);
				} else {
					this.indentation = 2;
				}
				var _ace = options.ace ? options.ace : ace;
				this.mode = options.mode == "code" ? "code" : "text";
				if (this.mode == "code") {
					if (typeof _ace === "undefined") {
						this.mode = "text";
						util.log("WARNING: Cannot load code editor, Ace library not loaded. Falling back to plain text editor");
					}
				}
				this.theme = options.theme || "ace/theme/jsoneditor";
				var me = this;
				this.container = container;
				this.dom = {};
				this.editor = void 0;
				this.textarea = void 0;
				this.width = container.clientWidth;
				this.height = container.clientHeight;
				this.frame = document.createElement("div");
				this.frame.className = "jsoneditor";
				this.frame.onclick = function(event) {
					event.preventDefault();
				};
				this.frame.onkeydown = function(event) {
					me._onKeyDown(event);
				};
				this.menu = document.createElement("div");
				this.menu.className = "menu";
				this.frame.appendChild(this.menu);
				var buttonFormat = document.createElement("button");
				buttonFormat.className = "format";
				buttonFormat.title = "Format JSON data, with proper indentation and line feeds (Ctrl+\\)";
				this.menu.appendChild(buttonFormat);
				buttonFormat.onclick = function() {
					try {
						me.format();
					} catch (err) {
						me._onError(err);
					}
				};
				var buttonCompact = document.createElement("button");
				buttonCompact.className = "compact";
				buttonCompact.title = "Compact JSON data, remove all whitespaces (Ctrl+Shift+\\)";
				this.menu.appendChild(buttonCompact);
				buttonCompact.onclick = function() {
					try {
						me.compact();
					} catch (err) {
						me._onError(err);
					}
				};
				if (this.options && this.options.modes && this.options.modes.length) {
					var modeBox = modeswitcher.create(this, this.options.modes, this.options.mode);
					this.menu.appendChild(modeBox);
					this.dom.modeBox = modeBox;
				}
				this.content = document.createElement("div");
				this.content.className = "outer";
				this.frame.appendChild(this.content);
				this.container.appendChild(this.frame);
				if (this.mode == "code") {
					this.editorDom = document.createElement("div");
					this.editorDom.style.height = "100%";
					this.editorDom.style.width = "100%";
					this.content.appendChild(this.editorDom);
					var editor = _ace.edit(this.editorDom);
					editor.setTheme(this.theme);
					editor.setShowPrintMargin(false);
					editor.setFontSize(13);
					editor.getSession().setMode("ace/mode/json");
					editor.getSession().setTabSize(this.indentation);
					editor.getSession().setUseSoftTabs(true);
					editor.getSession().setUseWrapMode(true);
					this.editor = editor;
					var poweredBy = document.createElement("a");
					poweredBy.appendChild(document.createTextNode("powered by ace"));
					poweredBy.href = "http://ace.ajax.org";
					poweredBy.target = "_blank";
					poweredBy.className = "poweredBy";
					poweredBy.onclick = function() {
						window.open(poweredBy.href, poweredBy.target);
					};
					this.menu.appendChild(poweredBy);
					if (options.change) {
						editor.on("change", function() {
							options.change();
						});
					}
				} else {
					var textarea = document.createElement("textarea");
					textarea.className = "text";
					textarea.spellcheck = false;
					this.content.appendChild(textarea);
					this.textarea = textarea;
					if (options.change) {
						if (this.textarea.oninput === null) {
							this.textarea.oninput = function() {
								options.change();
							};
						} else {
							this.textarea.onchange = function() {
								options.change();
							};
						}
					}
				}
			};
			textmode._onKeyDown = function(event) {
				var keynum = event.which || event.keyCode;
				var handled = false;
				if (keynum == 220 && event.ctrlKey) {
					if (event.shiftKey) {
						this.compact();
					} else {
						this.format();
					}
					handled = true;
				}
				if (handled) {
					event.preventDefault();
					event.stopPropagation();
				}
			};
			textmode._delete = function() {
				if (this.frame && this.container && this.frame.parentNode == this.container) {
					this.container.removeChild(this.frame);
				}
			};
			textmode._onError = function(err) {
				if (typeof this.onError === "function") {
					util.log("WARNING: JSONEditor.onError is deprecated. Use options.error instead.");
					this.onError(err);
				}
				if (this.options && typeof this.options.error === "function") {
					this.options.error(err);
				} else {
					throw err;
				}
			};
			textmode.compact = function() {
				var json = this.get();
				var text = JSON.stringify(json);
				this.setText(text);
			};
			textmode.format = function() {
				var json = this.get();
				var text = JSON.stringify(json, null, this.indentation);
				this.setText(text);
			};
			textmode.focus = function() {
				if (this.textarea) {
					this.textarea.focus();
				}
				if (this.editor) {
					this.editor.focus();
				}
			};
			textmode.resize = function() {
				if (this.editor) {
					var force = false;
					this.editor.resize(force);
				}
			};
			textmode.set = function(json) {
				this.setText(JSON.stringify(json, null, this.indentation));
			};
			textmode.get = function() {
				var text = this.getText();
				var json;
				try {
					json = util.parse(text);
				} catch (err) {
					text = util.sanitize(text);
					json = util.parse(text);
				}
				return json;
			};
			textmode.getText = function() {
				if (this.textarea) {
					return this.textarea.value;
				}
				if (this.editor) {
					return this.editor.getValue();
				}
				return "";
			};
			textmode.setText = function(jsonText) {
				if (this.textarea) {
					this.textarea.value = jsonText;
				}
				if (this.editor) {
					this.editor.setValue(jsonText, -1);
				}
			};
			module.exports = [
				{
					mode: "text",
					mixin: textmode,
					data: "text",
					load: textmode.format
				},
				{
					mode: "code",
					mixin: textmode,
					data: "text",
					load: textmode.format
				}
			];
		}
	});

	// src/js/JSONEditor.js
	var require_JSONEditor = __commonJS({
		"src/js/JSONEditor.js"(exports, module) {
			var treemode = require_treemode();
			var textmode = require_textmode();
			var util = require_util();
			function JSONEditor(container, options, json) {
				if (!(this instanceof JSONEditor)) {
					throw new Error('JSONEditor constructor called without "new".');
				}
				var ieVersion = util.getInternetExplorerVersion();
				if (ieVersion != -1 && ieVersion < 9) {
					throw new Error("Unsupported browser, IE9 or newer required. Please install the newest version of your browser.");
				}
				if (arguments.length) {
					this._create(container, options, json);
				}
			}
			JSONEditor.modes = {};
			JSONEditor.prototype._create = function(container, options, json) {
				this.container = container;
				this.options = options || {};
				this.json = json || {};
				var mode = this.options.mode || "tree";
				this.setMode(mode);
			};
			JSONEditor.prototype._delete = function() {
			};
			JSONEditor.prototype.set = function(json) {
				this.json = json;
			};
			JSONEditor.prototype.get = function() {
				return this.json;
			};
			JSONEditor.prototype.setText = function(jsonText) {
				this.json = util.parse(jsonText);
			};
			JSONEditor.prototype.getText = function() {
				return JSON.stringify(this.json);
			};
			JSONEditor.prototype.setName = function(name) {
				if (!this.options) {
					this.options = {};
				}
				this.options.name = name;
			};
			JSONEditor.prototype.getName = function() {
				return this.options && this.options.name;
			};
			JSONEditor.prototype.setMode = function(mode) {
				var container = this.container, options = util.extend({}, this.options), data, name;
				options.mode = mode;
				var config = JSONEditor.modes[mode];
				if (config) {
					try {
						var asText = config.data == "text";
						name = this.getName();
						data = this[asText ? "getText" : "get"]();
						this._delete();
						util.clear(this);
						util.extend(this, config.mixin);
						this.create(container, options);
						this.setName(name);
						this[asText ? "setText" : "set"](data);
						if (typeof config.load === "function") {
							try {
								config.load.call(this);
							} catch (err) {
							}
						}
					} catch (err) {
						this._onError(err);
					}
				} else {
					throw new Error('Unknown mode "' + options.mode + '"');
				}
			};
			JSONEditor.prototype._onError = function(err) {
				if (typeof this.onError === "function") {
					util.log("WARNING: JSONEditor.onError is deprecated. Use options.error instead.");
					this.onError(err);
				}
				if (this.options && typeof this.options.error === "function") {
					this.options.error(err);
				} else {
					throw err;
				}
			};
			JSONEditor.registerMode = function(mode) {
				var i, prop;
				if (util.isArray(mode)) {
					for (i = 0; i < mode.length; i++) {
						JSONEditor.registerMode(mode[i]);
					}
				} else {
					if (!("mode" in mode)) throw new Error('Property "mode" missing');
					if (!("mixin" in mode)) throw new Error('Property "mixin" missing');
					if (!("data" in mode)) throw new Error('Property "data" missing');
					var name = mode.mode;
					if (name in JSONEditor.modes) {
						throw new Error('Mode "' + name + '" already registered');
					}
					if (typeof mode.mixin.create !== "function") {
						throw new Error('Required function "create" missing on mixin');
					}
					var reserved = ["setMode", "registerMode", "modes"];
					for (i = 0; i < reserved.length; i++) {
						prop = reserved[i];
						if (prop in mode.mixin) {
							throw new Error('Reserved property "' + prop + '" not allowed in mixin');
						}
					}
					JSONEditor.modes[name] = mode;
				}
			};
			JSONEditor.registerMode(treemode);
			JSONEditor.registerMode(textmode);
		//	module.exports = JSONEditor;
			window.JSONEditor = JSONEditor; // fix by unixman
		}
	});

	// index.js
	var require_index = __commonJS({
		"index.js"(exports, module) {
			module.exports = require_JSONEditor();
		}
	});
	require_index();
})();

// #END
