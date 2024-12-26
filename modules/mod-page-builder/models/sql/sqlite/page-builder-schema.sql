
-- START :: SQLite: PageBuilder :: r.20220915 # smart.framework.v.8.7 ###

--

BEGIN;

-- Register in MetaData

INSERT INTO `_smartframework_metadata` (`id`, `description`) VALUES ('version@page-builder', 'v.2021.05.26');

-- Table Structure: Page-Builder #

CREATE TABLE 'page_builder' ( 								-- Web - Page Builder v.2021.05.26
	`id` CHARACTER VARYING(63) PRIMARY KEY NOT NULL, 		-- Unique ID for the Record: Page or Segment (segments must begin with: #)
	`ref` TEXT DEFAULT '[]' NOT NULL, 						-- Reference Parent IDs as Json-Array [], Optional
	`ctrl` CHARACTER VARYING(128) DEFAULT '' NOT NULL, 		-- Parent Controller ID, Optional
	`active` SMALLINT DEFAULT 0 NOT NULL, 					-- Active Status: 0=inactive ; 1=active
	`auth` SMALLINT DEFAULT 0 NOT NULL, 					-- Auth Status: 0 = no auth ; 1 = requires auth
	`special` INTEGER DEFAULT 0 NOT NULL, 					-- Ranking Special: 0..999999999
	`name` CHARACTER VARYING(255) DEFAULT '' NOT NULL, 		-- Record Name (for management only)
	`mode` CHARACTER VARYING(8) NOT NULL, 					-- Render Mode: html / markdown / text / raw / settings
	`data` TEXT DEFAULT '' NOT NULL, 						-- Render Active Runtime (Txt/B64)
	`code` TEXT DEFAULT '' NOT NULL, 						-- Render Code (Yaml/B64)
	`layout` CHARACTER VARYING(75) DEFAULT '' NOT NULL, 	-- Page Template (Pages Only) / Segment Area (Segments Only)
	`tags` TEXT DEFAULT '[]' NOT NULL, 						-- Tags as Json-Array [], Optional
	`checksum` CHARACTER VARYING(32) DEFAULT '' NOT NULL, 	-- Checksum (MD5)
	`translations` SMALLINT DEFAULT 0 NOT NULL, 			-- Allow Translations (1 = yes ; 0 = no)
	`counter` BIGINT DEFAULT 0 NOT NULL, 					-- Hit Counter
	`admin` CHARACTER VARYING(25) DEFAULT '' NOT NULL, 		-- Author
	`published` BIGINT DEFAULT 0 NOT NULL, 					-- Time of Publising: timestamp
	`modified` CHARACTER VARYING(23) DEFAULT '' NOT NULL 	-- Last Modification: yyyy-mm-dd
);
CREATE UNIQUE INDEX 'page_builder__unq__pkey' ON `page_builder` (`id` ASC);
CREATE INDEX 'page_builder__idx__ctrl' ON `page_builder` (`ctrl` ASC);
CREATE INDEX 'page_builder__idx__active' ON `page_builder` (`active` DESC);
CREATE INDEX 'page_builder__idx__auth' ON `page_builder` (`auth`);
CREATE INDEX 'page_builder__idx__special' ON `page_builder` (`special`);
CREATE INDEX 'page_builder__idx__mode' ON `page_builder` (`mode`);
CREATE INDEX 'page_builder__idx__translations' ON `page_builder` (`translations`);
CREATE INDEX 'page_builder__idx__counter' ON `page_builder` (`counter` DESC);
CREATE INDEX 'page_builder__idx__admin' ON `page_builder` (`admin` ASC);
CREATE INDEX 'page_builder__idx__modified' ON `page_builder` (`modified` DESC);


-- Table Structure: Page-Builder Translations #

CREATE TABLE 'page_translations' ( 							-- Web - Page (Builder) Translations v.2021.05.26
	`id` CHARACTER VARYING(63) NOT NULL, 					-- Unique ID for the Record: Page or Segment (segments must begin with: #)
	`lang` CHARACTER VARYING(2) NOT NULL, 					-- Language ID: de, fr, ro, ...
	`code` TEXT NOT NULL, 									-- Render Code (Txt/B64)
	`admin` CHARACTER VARYING(25) DEFAULT '' NOT NULL, 		-- Author
	`modified` CHARACTER VARYING(23) DEFAULT '' NOT NULL, 	-- Last Modification: yyyy-mm-dd
	PRIMARY KEY (`id`, `lang`)
);
CREATE UNIQUE INDEX 'page_translations__unq__pkey' ON `page_translations` (`id` ASC, `lang` ASC);
CREATE INDEX 'page_translations__idx__id' ON `page_translations` (`id` ASC);
CREATE INDEX 'page_translations__idx__lang' ON `page_translations` (`lang` ASC);
CREATE INDEX 'page_translations__idx__admin' ON `page_translations` (`admin` ASC);
CREATE INDEX 'page_translations__idx__modified' ON `page_translations` (`modified` DESC);

--

COMMIT;

--
-- END #
--