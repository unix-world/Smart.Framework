
// Check JS Runtime
// (c) 2021 unix-world.org
// r.20210511

(function(){
	'use strict';
	var checkJsRuntime = function() {
		try {
			var testPassFx = () => {
				const ES6FxClass = Function('const testES6 = new class{constructor(){ let es6Test = true; const Es6Test = () => { return !! es6Test; }; this.Es6Test = Es6Test; }}; return testES6;'); // ES6 have to support arrow type macros and classes as well as const and let syntax, it is a standard
				if(typeof(ES6FxClass) != 'function') {
					return 'Cannot define ES6 Function';
				} //end if
				let ES6Class = ES6FxClass();
				if(typeof(ES6FxClass) != 'function') {
					return 'ES6 Function Class is not accesible';
				} //end if
				return ES6Class.Es6Test();
			};
			return testPassFx();
		} catch(err) {
			return String(err);
		} //end try/catch
		//--
	};
	window.checkJsRuntime = checkJsRuntime; // export
})();

// #END
