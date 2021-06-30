// CodeMirror, copyright (c) by Marijn Haverbeke and others
// Distributed under an MIT license: https://codemirror.net/LICENSE

// codemirror: v.5.61.1
// modified by unixman

(function(mod) {
	if (typeof exports == "object" && typeof module == "object") // CommonJS
		mod(require("../lib/codemirror"));
	else if (typeof define == "function" && define.amd) // AMD
		define(["../lib/codemirror"], mod);
	else // Plain browser env
		mod(CodeMirror);
})(function(CodeMirror) {
	"use strict";

	CodeMirror.modeInfo = [ // {{{SYNC-SMART-CODEMIRROR-MODES}}}
		{name: "PGP", mimes: ["application/gpg", "application/pgp", "application/pgp-encrypted", "application/pgp-keys", "application/pgp-signature"], mode: "asciiarmor", ext: ["gpg", "asc", "pgp", "sig"]},
		{name: "CSS", mime: "text/css", mode: "css", ext: ["css"]},
		{name: "diff", mime: "text/x-diff", mode: "diff", ext: ["diff", "patch"]},
		{name: "DTD", mime: "application/xml-dtd", mode: "dtd", ext: ["dtd"]},
		{name: "Embedded JavaScript", mime: "application/x-ejs", mode: "htmlembedded", ext: ["ejs"]},
		{name: "Go", mime: "text/x-go", mode: "go", ext: ["go"]},
		{name: "HTML", mime: "text/html", mode: "htmlmixed", ext: ["html", "htm", "handlebars", "hbs"], alias: ["xhtml"]},
		{name: "HTTP", mime: "message/http", mode: "http"},
		{name: "JavaScript", mimes: ["text/javascript", "text/ecmascript", "application/javascript", "application/x-javascript", "application/ecmascript"], mode: "javascript", ext: ["js"], alias: ["ecmascript", "js", "node"]},
		{name: "JSON", mimes: ["application/json", "application/x-json"], mode: "javascript", ext: ["json", "map"], alias: ["json5"]},
		{name: "JSON-LD", mime: "application/ld+json", mode: "javascript", ext: ["jsonld"], alias: ["jsonld"]},
		{name: "Markdown", mime: "text/x-markdown", mode: "markdown", ext: ["markdown", "md", "mkd"]},
		{name: "mbox", mime: "application/mbox", mode: "mbox", ext: ["mbox"]},
		{name: "PHP", mimes: ["text/x-php", "application/x-httpd-php", "application/x-httpd-php-open"], mode: "php", ext: ["php", "php3", "php4", "php5", "php7", "phtml"]},
		{name: "Plain Text", mime: "text/plain", mode: "null", ext: ["txt", "text", "conf", "def", "list", "log"]},
		{name: "Properties files", mime: "text/x-properties", mode: "properties", ext: ["properties", "ini", "in"], alias: ["ini", "properties"]},
		{name: "Scheme", mime: "text/x-scheme", mode: "scheme", ext: ["scm", "ss"]},
		{name: "Shell", mimes: ["text/x-sh", "application/x-sh"], mode: "shell", ext: ["sh", "ksh", "bash"], alias: ["bash", "sh", "zsh"], file: /^PKGBUILD$/},
		{name: "SQL", mime: "text/x-sql", mode: "sql", ext: ["sql"]},
		{name: "TOML", mime: "text/x-toml", mode: "toml", ext: ["toml"]}, // compatible with PHP INI Files
		{name: "XML", mimes: ["application/xml", "text/xml"], mode: "xml", ext: ["xml", "xsl", "xsd", "svg"], alias: ["rss", "wsdl", "xsd"]},
		{name: "YAML", mimes: ["text/x-yaml", "text/yaml"], mode: "yaml", ext: ["yaml", "yml"], alias: ["yml"]},
	];

	// Ensure all modes have a mime property for backwards compatibility
	for (var i = 0; i < CodeMirror.modeInfo.length; i++) {
		var info = CodeMirror.modeInfo[i];
		if (info.mimes) info.mime = info.mimes[0];
	}

	CodeMirror.findModeByMIME = function(mime) {
		mime = mime.toLowerCase();
		for (var i = 0; i < CodeMirror.modeInfo.length; i++) {
			var info = CodeMirror.modeInfo[i];
			if (info.mime == mime) return info;
			if (info.mimes) for (var j = 0; j < info.mimes.length; j++)
				if (info.mimes[j] == mime) return info;
		}
		if (/\+xml$/.test(mime)) return CodeMirror.findModeByMIME("application/xml")
		if (/\+json$/.test(mime)) return CodeMirror.findModeByMIME("application/json")
	};

	CodeMirror.findModeByExtension = function(ext) {
		ext = ext.toLowerCase();
		for (var i = 0; i < CodeMirror.modeInfo.length; i++) {
			var info = CodeMirror.modeInfo[i];
			if (info.ext) for (var j = 0; j < info.ext.length; j++)
				if (info.ext[j] == ext) return info;
		}
	};

	CodeMirror.findModeByFileName = function(filename) {
		for (var i = 0; i < CodeMirror.modeInfo.length; i++) {
			var info = CodeMirror.modeInfo[i];
			if (info.file && info.file.test(filename)) return info;
		}
		var dot = filename.lastIndexOf(".");
		var ext = dot > -1 && filename.substring(dot + 1, filename.length);
		if (ext) return CodeMirror.findModeByExtension(ext);
	};

	CodeMirror.findModeByName = function(name) {
		name = name.toLowerCase();
		for (var i = 0; i < CodeMirror.modeInfo.length; i++) {
			var info = CodeMirror.modeInfo[i];
			if (info.name.toLowerCase() == name) return info;
			if (info.alias) for (var j = 0; j < info.alias.length; j++)
				if (info.alias[j].toLowerCase() == name) return info;
		}
	};
});

// #END
