
-- START :: SQLite: Web/PageBuilder @ SampleData r.20220915 #

BEGIN;

INSERT INTO `page_builder` VALUES ('raw-page', '[]', '', 1, 0, 0, 'Raw Page', 'raw', 'UFJPUFM6CiAgRmlsZU5hbWU6IHRlc3QudHh0CiAgRGlzcG9zaXRpb246IGlubGluZQ==', 'VGhpcyBpcyBhIHNhbXBsZSByYXcgcGFnZSB0ZXN0IDw/IC4uLg==', '', '[]', '0d029b2686c3c3564d00596788f85831', 0, 1, 'admin', 1521213679, '2019-01-09 18:29:57');
INSERT INTO `page_builder` VALUES ('pagina-unu', '[]', '', 0, 0, 0, 'Pagina Unu HTML', 'html', 'UkVOREVSOg0KICBURU1QTEFURUBBUkVBLlRPUDoNCiAgICBjb250ZW50Og0KICAgICAgdHlwZTogc2VnbWVudA0KICAgICAgaWQ6IHdlYnNpdGUtbWVudQ0KICBURU1QTEFURUBBUkVBLkZPT1RFUjoNCiAgICBjb250ZW50Og0KICAgICAgdHlwZTogc2VnbWVudA0KICAgICAgaWQ6IHdlYnNpdGUtZm9vdGVy', '', '', '[]', '6fb72f977c4810cad21cc810d2bc0bf9', 0, 1, 'admin', 1509729671, '2018-05-25 11:32:49');
INSERT INTO `page_builder` VALUES ('#sub-segment-a', '["#my-segment-1"]', '', 0, 0, 0, 'Test SUB-Segment A', 'html', 'I1JFTkRFUjoNCiMgIFRFU1QtQ0lSQ1VMQVI6DQojICAgIGNvbnRlbnQ6DQojICAgICAgdHlwZTogc2VnbWVudA0KIyAgICAgIGlkOiBteS1zZWdtZW50LTEgIyBodG1sIHNlZ21lbnQ=', 'PGZvbnQgY29sb3I9IiNmZmZmZmYiPjxzcGFuIHN0eWxlPSJiYWNrZ3JvdW5kLWNvbG9yOiByZ2IoNTEsIDEwMiwgMjU1KTsiPlN1YkBTZWdtZW50K0E8L3NwYW4+PC9mb250Pg0KDQo8YnI+DQoNCjxicj57ezpURVNULUNJUkNVTEFSOn19', '', '[]', 'f699ab6a913bc807791cd2e18f78c28d', 0, 1, 'admin', 1475172564, '2018-05-25 11:31:22');
INSERT INTO `page_builder` VALUES ('#seg-plug', '[]', '', 0, 0, 0, 'Segment with Plugin', 'html', 'UkVOREVSOg0KICBQTFVHSU46DQogICAgY29udGVudDoNCiAgICAgIHR5cGU6IHBsdWdpbg0KICAgICAgaWQ6IHBhZ2UtYnVpbGRlci90ZXN0Mg0KICAgICAgY29uZmlnOiBteS1zZWdtZW50LTU=', 'e3s6UExVR0lOOn19', '', '[]', '7023b901a75af54d2f0f7a5169d6514b', 0, 1, 'admin', 1522320028, '2018-05-25 11:29:17');
INSERT INTO `page_builder` VALUES ('#my-segment-5', '[]', '', 0, 0, 0, 'Test Segment #5', 'settings', 'U0VUVElOR1M6DQogICAgYTogMjAwDQogICAgYjogJ3RoaXMgaXMn', '', '', '[]', 'bc28e11689a403eccc52d2cce100bf7e', 0, 1, 'admin', 1475171655, '2018-05-25 11:29:30');
INSERT INTO `page_builder` VALUES ('test-page', '[]', '', 1, 0, 0, 'Test Page (HTML)', 'html', 'UkVOREVSOgogIFRFU1Q6CiAgICBjb250ZW50OgogICAgICB0eXBlOiBzZWdtZW50CiAgICAgIGlkOiBteS1zZWdtZW50LTIgIyBodG1sIHNlZ21lbnQKICBBUkVBLU9ORToKICAgIGNvbnRlbnQ6CiAgICAgIHR5cGU6IHNlZ21lbnQKICAgICAgaWQ6IG15LXNlZ21lbnQtMyAjIGh0bWwgc2VnbWVudAogIEFSRUEuVFdPOgogICAgY29udGVudC0xOgogICAgICB0eXBlOiBwbHVnaW4KICAgICAgaWQ6IHBhZ2UtYnVpbGRlci90ZXN0MQogICAgICBjb25maWc6CiAgICAgICAgdGl0bGU6IE15IFBsdWdpbgogICAgICAgIGNvbHVtbnM6IDEwMAojICAgIGNvbnRlbnQtMjoKIyAgICAgIHR5cGU6IHBsdWdpbgojICAgICAgaWQ6IGFub3VuY2VtZW50cy9tYWluCiAgICBjb250ZW50LTQ6CiAgICAgIHR5cGU6IHNlZ21lbnQKICAgICAgaWQ6IG15LXNlZ21lbnQtMgogICAgY29udGVudC0zOgogICAgICB0eXBlOiBzZWdtZW50CiAgICAgIGlkOiBteS1zZWdtZW50LTMgIyBtYXJrZG93biBzZWdtZW50CiAgQVJFQS1USFJFRToKICAgIGNvbnRlbnQ6CiAgICAgIHR5cGU6IHBsdWdpbgogICAgICBpZDogcGFnZS1idWlsZGVyL3Rlc3QyCiAgICAgIGNvbmZpZzogbXktc2VnbWVudC01CiAgQVJFQS1GT1VSOgogICAgY29udGVudDoKICAgICAgdHlwZTogc2VnbWVudAogICAgICBpZDogbXktc2VnbWVudC0xCiAgQVJFQS1GSVZFOgogICAgY29udGVudC0xOgogICAgICB0eXBlOiBwbHVnaW4KICAgICAgaWQ6IHBhZ2UtYnVpbGRlci90ZXN0MwogICAgICBjb25maWc6CiAgICAgICAgdGl0bGU6IE5ld3MKICAgICAgICBjb2x1bW5zOiAxMAogICAgY29udGVudC0yOgogICAgICB0eXBlOiBzZWdtZW50CiAgICAgIGlkOiBteS1zZWdtZW50LTIKICAgIGNvbnRlbnQtMzoKICAgICAgdHlwZTogcGx1Z2luCiAgICAgIGlkOiBwYWdlLWJ1aWxkZXIvdGVzdDQKICBURU1QTEFURUBBUkVBLlRPUDoKICAgIGNvbnRlbnQ6CiAgICAgIHR5cGU6IHNlZ21lbnQKICAgICAgaWQ6IHdlYnNpdGUtbWVudQogIFRFTVBMQVRFQEFSRUEuRk9PVEVSOgogICAgY29udGVudDoKICAgICAgdHlwZTogc2VnbWVudAogICAgICBpZDogd2Vic2l0ZS1mb290ZXIKICBURU1QTEFURUBUSVRMRToKICAgIGNvbnRlbnQ6CiAgICAgIHR5cGU6IHZhbHVlCiAgICAgIGlkOiBUaGlzIGlzIHRoZSBwYWdlIDx0aXRsZT4KICAgICAgY29uZmlnOiB0ZXh0', 'VGhpcyBpcyBhIHRlc3QNCg0KPGI+cGFnZTwvYj4NCg0KISENCg0KPGJyPg0KDQo8YnI+e3s6VEVTVDp9fQ0KDQo8YnI+DQoNCjxicj4NCg0KPHRhYmxlIHN0eWxlPSJ3aWR0aDoxMDAlOyIgY2VsbHBhZGRpbmc9IjIiIGNlbGxzcGFjaW5nPSIyIiBib3JkZXI9IjEiPjx0Ym9keT4NCg0KPHRyIHZhbGlnbj0idG9wIj4NCg0KPHRkPnt7OkFSRUEtT05FOn19PC90ZD4NCg0KPHRkPnt7OkFSRUEuVFdPOn19PC90ZD4NCg0KPC90cj4NCg0KPC90Ym9keT48L3RhYmxlPg0KDQo8YnI+DQoNCnt7OkFSRUEtVEhSRUU6fX0NCg0KPGJyPg0KDQo8aHI+DQoNCjx0YWJsZT48dHI+PHRkPnt7OkFSRUEtRk9VUjp9fTwvdGQ+PHRkPnt7OkFSRUEtRklWRTp9fTwvdGQ+PC90cj48L3RhYmxlPg0KDQo8aHI+', '', '[]', '1e4475d45724d618f201799c6e04c7fe', 0, 1, 'admin', 1475171339, '2019-01-09 18:31:31');
INSERT INTO `page_builder` VALUES ('#my-segment-2', '["test-page"]', '', 0, 0, 0, 'Test Segment #2', 'html', '', 'PGZvbnQgY29sb3I9IiNmZjAwMDAiPjxiPlRoaXMgaXMgc2VnbWVudCAyPC9iPjwvZm9udD4NCg0KPGJyPg==', '', '[]', 'c9134079041ca24db731f4a62a61e8f4', 0, 1, 'admin', 1475171599, '2018-05-10 09:31:16');
INSERT INTO `page_builder` VALUES ('#my-segment-3', '["test-page"]', '', 0, 0, 0, 'Test Segment #3', 'markdown', 'Iw==', 'IyBIMSAoc2VnbWVudCAzKQ0KDQohW0ltYWdlIDFdKHdwdWIvd2ViLWNvbnRlbnQvcmVkLWRyYWdvbmZseS5qcGcgIkltYWdlIDEiKSB7QHdpZHRoPTIwMH0=', '', '[]', '99dfdaa6e13a6c5a6a35cc90bf29a254', 0, 1, 'admin', 1475171629, '2018-05-25 11:30:06');
INSERT INTO `page_builder` VALUES ('#my-segment-1', '["test-page"]', '', 0, 0, 0, 'Test Segment #1', 'html', 'UkVOREVSOg0KICBURVNULVNVQi1TRUdNRU5UOg0KICAgIGNvbnRlbnQ6DQogICAgICB0eXBlOiBzZWdtZW50DQogICAgICBpZDogc3ViLXNlZ21lbnQtYSAjIGh0bWwgc2VnbWVudA==', 'PHU+PGk+PGI+RGFzIGlzdCBTZWdtZW50IDE8L2I+PC9pPjwvdT4NCg0KPGJyPg0KDQo8YnI+e3s6VEVTVC1TVUItU0VHTUVOVDp9fQ0KDQo8YnI+', '', '[]', '56b42f5d7b1f0c7eb50e83f029f5ef37', 0, 1, 'admin', 1475172174, '2018-05-25 11:30:16');
INSERT INTO `page_builder` VALUES ('#website-menu', '["test-page", "pagina-unu"]', '', 0, 0, 1, '@ Website Menu @', 'html', '', 'QFRoaXMgaXMgdGhlIE1lbnVADQoNCjxicj4=', '', '[]', '0e38cb5e7114bba0a0dfdef6cfec0cea', 0, 1, 'admin', 1475171957, '2018-05-10 09:28:54');
INSERT INTO `page_builder` VALUES ('#website-footer', '["test-page", "pagina-unu"]', '', 0, 0, 0, '@ Website Footer @', 'html', '', 'PGRpdiBzdHlsZT0iYmFja2dyb3VuZDojMzMzMzMzOyBjb2xvcjojRkZGRkZGOyB3aWR0aDoxMDAlOyBtaW4taGVpZ2h0OjMwMHB4OyI+DQoNCiAgPGgyPlRoaXMgaXMgdGhlIGZvb3RlciBhcmVhPC9oMj4NCg0KPC9kaXY+', '', '[]', 'dccc3a6b886025ea7a95559e6951b26a', 0, 1, 'admin', 1522167945, '2018-05-10 09:29:09');
INSERT INTO `page_builder` VALUES ('#segment-with-markers', '[]', '', 0, 0, 0, 'Segment with Markers', 'markdown', '', 'IyBUaGlzIGlzIGEgc2VnbWVudCB3aXRoIG1hcmtlcnMKClRoaXMgaXMgYSBzYW1wbGUgbWFya2VyOiB7ez0jVEhFLU1BUktFUnxodG1sIz19fQoK', '', '[]', 'ec3aa830eb5685e18cc293b73d2085ea', 1, 6, 'admin', 1542709584, '2018-11-20 10:27:10');

COMMIT;

--
-- END #
--
