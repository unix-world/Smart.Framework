<?php
// [@[#[!SF.DEV-ONLY!]#]@]
// Controller: Samples/EcdsaTest
// Route: ?page=samples.ecdsa-test&encryptedprivkey=yes|no&nonasn1=no|yes
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

define('SMART_APP_MODULE_AREA', 'SHARED');

/**
 * Abstract Controller
 *
 * @ignore
 *
 */
abstract class SmartAppAbstractController extends SmartAbstractAppController {

// go client certificate/privKey/pubKey from create-x509-certs.go

private const theGoCertPEM = '
-----BEGIN CERTIFICATE-----
MIIEQjCCA6OgAwIBAgIhAI1mne3/IrIVwpx7FMS0+Ri8n8xyza79jdNwYkoGqUrb
MAoGCCqGSM49BAMEMIHdMQswCQYDVQQGEwJVSzEPMA0GA1UECBMGTG9uZG9uMQ8w
DQYDVQQHEwZMb25kb24xHDAaBgNVBAkTE1N0cmVldCBOb05hbWUgbm8uMDAxETAP
BgNVBBETCFpFUk8wMDAwMQ8wDQYDVQQKEwZnb2xhbmcxEjAQBgNVBAMTCWxvY2Fs
aG9zdDFWMFQGA1UEBRNNMjE3MTM4NDYzOTc2ODgzNTU2MDUxMTExODE3MDU4OTY3
MjAzMDM4MjU3NjkxOTg4Mjc1NjM3Njk0MjIwOTM0NTc0OTY3MDgzMDgwNzEwIBcN
MjYwMTE0MDc1NjUxWhgPMjEyNjAxMTQwNzU2NTJaMIHjMQswCQYDVQQGEwJSTzEN
MAsGA1UECBMEQ2x1ajEUMBIGA1UEBxMLQ2x1ai1OYXBvY2ExGDAWBgNVBAkTD05v
IFN0cmVldCBuby4wMDENMAsGA1UEERMEMzQwMDEQMA4GA1UEChMHc21hcnRnbzEc
MBoGA1UEAwwTd2VibWFzdGVyQGxvY2FsaG9zdDFWMFQGA1UEBRNNNjM5NTc0MjAw
NDE0MDA2Njk1MzEyNTQ2NDI5Njc4NDk2MjI4MzkxNTk4MzUxODYwNTU3NTIyODg4
NjUzNzQ3NDEzNTg0NjE5MzAyMDMwgZswEAYHKoZIzj0CAQYFK4EEACMDgYYABAA/
Oo9iGQ2FdYzdXOFPf2CNe4HaYzZ6UqYkbl6u9lwVsR5manMAYWFdZd/MCHYco6J2
6tN3Ht5oA92dbR+5JzQlYwEECuyip73VJzfTSie3yvTdi+olVY9z/kisEpbQnycz
WAODb/mddFgO8YAlezux+9Zd3hZF1Z4BlM9jy/y4mvPg7qOB5jCB4zAOBgNVHQ8B
Af8EBAMCA/gwOwYDVR0lBDQwMgYIKwYBBQUHAwQGCCsGAQUFBwMDBggrBgEFBQcD
CAYIKwYBBQUHAwEGCCsGAQUFBwMCMAwGA1UdEwEB/wQCMAAwMwYDVR0OBCwEKjAx
S0EwOEwyV0s3U0JFRFZSLVhXVDM1NkZIQ0kzMjUtNzY1OTc4Mzk0ODA1BgNVHSME
LjAsgCowMUtBMDhMMldKVVIyOEVCQi03MllHMFJQVUcyUEM4LTI0NDU4NTYzNzAw
GgYDVR0RBBMwEYIJbG9jYWxob3N0hwR/AAABMAoGCCqGSM49BAMEA4GMADCBiAJC
AMk5shqeDHBf2NjGTe3q2+PgCb0wBVTAvYKdjddwt+/1b/Dxx/JnN/ZPAvWwM4rT
WPsadEWzlvlPZ9Fh8Fgq5CjZAkIAiBUPUoKxE9xyGzwLVEGNwlMdCzlqfxZcy06i
ZMQuZUCd6sLnwFF3wUHr9I/59xSoveGMaE0oWlR5+JOhXgjvujs=
-----END CERTIFICATE-----
';

private const theGoPrivkeyPEM = '
-----BEGIN PRIVATE KEY-----
MIHuAgEAMBAGByqGSM49AgEGBSuBBAAjBIHWMIHTAgEBBEIAbNNfDZ+70FESt19B
6Bt2Qw8FQ0QWCwKFUEomn6Nn81rAS/JtPInXBZzt6v98wwQ3j1h3t3p6W7h4WkLg
dfUjpuehgYkDgYYABAA/Oo9iGQ2FdYzdXOFPf2CNe4HaYzZ6UqYkbl6u9lwVsR5m
anMAYWFdZd/MCHYco6J26tN3Ht5oA92dbR+5JzQlYwEECuyip73VJzfTSie3yvTd
i+olVY9z/kisEpbQnyczWAODb/mddFgO8YAlezux+9Zd3hZF1Z4BlM9jy/y4mvPg
7g==
-----END PRIVATE KEY-----
';

private const theGoPubkeyPEM = '
-----BEGIN PUBLIC KEY-----
MIGbMBAGByqGSM49AgEGBSuBBAAjA4GGAAQAPzqPYhkNhXWM3VzhT39gjXuB2mM2
elKmJG5ervZcFbEeZmpzAGFhXWXfzAh2HKOidurTdx7eaAPdnW0fuSc0JWMBBArs
oqe91Sc300ont8r03YvqJVWPc/5IrBKW0J8nM1gDg2/5nXRYDvGAJXs7sfvWXd4W
RdWeAZTPY8v8uJrz4O4=
-----END PUBLIC KEY-----
';

private const theGoSignatures = [
	'MIGIAkIAhwU2gCi0PBxsJfp2ZLmW3kGAkil0XsOpeBz9lgfGeSdFaeJ3PBprwB+o5RpSR5RaOC9BoRh73XGPlIpTaDdqQ9ICQgHih3jicW626CiPYrDarYXDDnCGg+/dQQgaEKJ33Eyare5fTX7VihW+NlXhlMm4Bt/qKjUSPD6kzu33P2mTL34gkQ==',
	'MIGHAkIBy374px9YXUgipQgxl+zP6in8KKsUInyl73LbAK0CwYwxg6SsXED6EOrLkJXUEmeYwAbHU6Su1aWCZ9JzpEQ4evMCQSMKFSx67WoeWOsomA2Fs0m8DrFVuMvNKkTyH1BhnwJCka9iVK4EfyC0uCVSeiQPHTr9K97zmpAIEdIBglpB2Cpt',
	'MIGHAkFFMS7SAs+nM278U28YQFHwdfnQrgcrRB3XXQGFSbbLs5Zu4y2orL7yYe+xVJUR29KkTQSKIRMUJg3umhBy5VtpQQJCAf7zMhgCquc0YSPOY44SGeCBMT4AzG3DytvhFejMo6BLS6AogTHks+yJ/l5kGAKOAek3W0o9YFIsS83H+jTkdkQn',
	'MIGHAkF30QJPMjYde4snM/kywo3FgFoLSdmJ2f2H8ezNLuh8ibdAMtYOa9yFX8105OawYSU8C0/rkmLpVoZK+u5FwcA+bAJCAfuDUfJ910oumf+PkKmfnPCWFpHZxHDItOu82frM6JU0NQDUmcnauSTVbl9f0Ajopl1qNHiL3hMqQq2lRL0MMk3+',
	'MIGIAkIB97J+VihACJyaPdlVk+kNexjJ8O6gKSpM+78ivLM3BfrXlMskaqr2tXshb7IKUp5c/V86bRDz913b6BKGlBefG7oCQgHQrTqYpg7tTC4uLKoHQ12sIHZGfCGOfIj9NVpWnstxLZEi+6qNtMIBGJsU+quLaYk+W0imI7PKjnRELdWMaaKIEQ==',
	'MIGHAkFMe+snjS30TzDaJaxwCvdjEs9yo7VgIUfdH8lm7PfsDTQZ6NjqhVn24RjS0QA4EsrVd40ao9WSAUjTGnNs5WOjWgJCANsxdhf6J7r0os2BCVJlbcJZCxDrY4K4rF4jEy0kq586yEmANri/as0pSq7acjq8U/exOEz9UgrVD8H6Iku/TnzN',
	'MIGIAkIAsaJYMBIVTeHmmagJsFDum9QrGSlr++IIw4VVGCI8tbcX0QHHp6Y5qjf3y41BwQyiH5OtbwTqVIo4fBz/eJeGdZkCQgCRbpd0lhsUVo2sTutQBo8cTjmFemhZ1wHa0jPV6t4vDEl0BZKqMy9aFyxhIsyCEGesfO5ZL8liDyu1LEeaJ/blfw==',
	'MIGHAkIA1GABilcTvzfK3HWkmsHBDhk1V1myv+MlDrYdA3NcfbEjgVpEPEO0JFSCH97D8A1Ho0WC0MtNEh7ZV7sgSnZufNoCQTkTOZWgqXPbxXBYFUjSh7jTON6Am+uSc350vkoYfH0OY4K0S+d2cIMU6LOSFtrUc36Dwt3Kz9EIDPxik7CR1HZe',
	'MIGIAkIA31OfKDcGnPbvaBj2m8heES5QabzcldTSgt3C1Kfdb6cbgxwKIIqJe5PRkDxCzx4GPRgMAag0olQCQ5NnUztVXi8CQgG9bUuJ4r2m1Y2gxAjZcdgIOjag5rpAX7Nha3pPYGbc4FL3K5pxc0RrDvo8RNHaSlSvEJXIIVNnskr470Z1UfRPLQ==',
	'MIGHAkF+44dEA0Nhs7a6+wPhYH3Vi2drl3J/OhK7RkE2o/6dr8T+aXx0M4JrEP+fdT9p1uLQc1Z1rxE67IQr8GDjcaMi/gJCAbsbcxS8fbzE8g8JXIbItekXbktfSetGF7n25PcphPtuTNFNQmsg5GMNLY9Z4GEXB/d/1dYATvlL6vUT0sFC8p5L',
	'MIGHAkEDjy+tIDv9IDsdutJJQNf6pLpdLsgiZIGUJ/4vPUMOdaL20lZ6+eWAxk790gJNjZCTgNO/LhafNmXWuowRiuMiyQJCAIMaTsdnhV9vhBiGBv3YAfbg/KDaT8dEOcGJ/iT626VtktImLqV64S6GHg4GnHXmv8A2VD3CRGGR3wwfHgW5D35M',
	'MIGIAkIBkJ0hREPT7H1brQTP3s93p0vD1l3DO329unVPwZdTFxYBsRKcL/HDkWaJkspci/QrxOwJxcEX/kKcvLCsYFCBweYCQgCfzj73igvKYNTqhSKv0IctI+JgdZqgBwgAJ0jD7KJl3JRIbfBxO96K9H0D1DYdQorRO3P9DHcijDNfvsEAH6jvYg==',
	'MIGHAkIBVIXTjGA3ol+1jibOVSGneQcC+j36BWFbjv+8h/7ahzNS32nb1ucpgva7aaQF/DqfTBPMWJyakg2TuwFTOaSkCwgCQUH0ZCOZYDhwqUygkEK7LItP1U8rmRSEjhdVS/cPSgKsN5nvIrqeoaWbg8ZMS0cpC9exd47D5a0xnYMupROMY4Zl',
	'MIGIAkIBKzsqjsl6x8ljfJzC9AY+TCGUw4pCwnePzmVltTBou4gnn9a+BodhFtFOHBJFj5GLDevjw8eF24AHIgI706AJ7IwCQgFSZXQYLzxbzqIOPJtLKTW2F/gHPWaIm+pIz8hVazkUuRbZFFMgRyFbz0yThY8AqZTRs9Jg1dXydIpBdfu1KkoRJg==',
	'MIGIAkIBrNP7qzjbGCHGk1WjW2L7JoALi9VK4MPzcwEEDs71JRFdRZ/rfjWAbNXLJpUOVPwVM0RjaMrKfi3eDP11AAALecwCQgHBXgWjLuQnhyVoDzbpHaThuG/NTsaBXUYKi94avjvr7XavhMPasOLzmtfz4e50kaXsG4OOR7JqKSxTFMYxEXxJ8Q==',
	'MIGHAkIApU+Qu7Mo4rudULJnjT1R4qm5BLW+St+QJwzcG6Nnm/Tl8xy5m0mXnq9sVbQQwXna3PpNjboOjo2bmjFTw25DLk8CQQhNkkJm7oyoD2GHvTqGziF17v/bX0OiaH5iS8GtY3lKIWUH3iQybcfyfK0LToEoXTiaWbRC5GkPD18Caok4wr4w',
	'MIGIAkIAxcD0hpi/C5KfxVdi5/qCMUIBraiYJ6Nda/4QCkbObbvlODRMpU4nGSk8UIaJDbGXSNeXWTlYG1mQC8dTvh/9f0QCQgEUSCdQb1pN7SyjmHlgjvyfVtc4XJCuskSgRuel9RGjubClZmRBTT5OgBlkibE6khiqgpEJIwxD+y1m5Bd9x0isdA==',
	'MIGIAkIB5vpetrtF14N69yBEQ5x4ImDsRAoGS8vUocmrYiK0D+MS2sU8YBh7qqTuDfJlxpGnnpCwyB04LC1VtHaDRgcppYUCQgGsNnFzlk2R8oKtr7NxiuUQ4w7W/Yl/TU14lQ33fW1YEcYNtCzOn6QeFAjRJF4blSeXjyeCAjSSD3H1uQ5akJUG0g==',
	'MIGIAkIBICZJEvhCvs3SGv1qvHRpfPjsfLIGT+73LBWbC6CLQyL83xncYUp0eZ+sGMplSS63ZhRMkuTxME7cwjwkJsiNL9sCQgCplzDkJOSpdOaAIkB4H7IU7v2zT5fHxuClhQsvO0BA0fY8ufTj7BAUy0APXNoXCgyQTnXP8ekQ60ldsVurjNDGLQ==',
	'MIGIAkIBPaoBarTooKyN/0Nq17MLJn/Z5b+8RUkm+vT9N/C7tCaDX8urEqLXgszYcslqkFzPFEcWApNrklSl7Vg7u++5q/oCQgFMPGgsrozxv4ZHibs4TiAZmruJZ0rH8R4oPrr0M89iEmsPX53Sx6260AFPwdH/NzxPiqYByYwhwJRAIpJj8U/zIA==',
	'MIGIAkIB+NbI+bx5/g1nTd2gogj4bIG++K3e+zmRZd9Q7bUb5i+zocXKqXKMLzBcoOieiedH4QDWL3kaiA3zk57d6IFHEZICQgCjVF0LVyIYiCdFV1Td1m6V69ImtAlHaX/AMcqFdFtPCzB/xEt9OBt/ii//eS2QIGGtyhxVMw0qgddYyVWt5VxXMA==',
	'MIGIAkIBQVuxQu8Fm2m5/bJmnFuSF2201n3RCE2OgmZ/ztxR0tsLEajZk3TtO0/lD3Mspt0h7tdqQ/AzHOEawcT+RkzZJkYCQgD7GaLYRc1qqmq+GxNMRJnP1tebovs0RBtRUEZWwiGW8/k05ur5BOEk/11Pd1yq/UCcZu4oiSHShNwp8twTZAtzsQ==',
	'MIGHAkIBbvimDlBKO8+GO4t4URR+sUY9xj6PF9WafemPogZaEWhb3brLRLZBTh+1g/dROdfuJQaVxOYAdxzH6lqKDvjBd1oCQSKqhzf0p90fID/eCZbtmN42Bz+x8Q1CFsgE6h/aRHPVDt1ie7tlM4vkPG/rfPZh5jm62yTHLQG1tYLbu9SV5F71',
	'MIGIAkIA+aGcXoAasmUlNCK+bog1lm9fi+xhqZl55JthaTDm46BQdpBsgBihyzx8Rl1Js7DV0aTUf7sZwAQ92EE2xfLwz1QCQgCtx0V+s0Fy9cv5WTWOQWA2BJ1uuSLap9VY42FC1qUTfbDcbtWKgRQRD0/sTnerQOHvAt4Cqk8OkTPx3sXbmh/kAg==',
	'MIGHAkEh46P5DXYKIsIZNEqiysyCnNPQTsQ5pCocH0OdqiXDQAFDt4BWprdASXSY9uU26C5GaCy9RrQOICyr6sDnRpw7hgJCAQIahu04QQPcjW4PFWB/rMDXILrAUordKNVOS9sekf0GFCgAEObeHKnJrJ4cQiDn/c6+1wTxOknyTmrQcCqWknRl',
	'MIGHAkFziBtaDF87GIOxwjE9P/mPjJ4WSYcERnVlyo/kA3wXiEzGf0aRxYpAqSIJ9bfLzk05zIDYYFLNpTBLST7gZSxhkQJCAUwTd7OPtsPy1OaGcZo/eS1zK0RxStIELi+ttWD6f9CCT76mDUzKQBrHcnO6AA1AfFryfDk/nIBRlYnQ4C9CjXk0',
	'MIGIAkIA9yfVq/4CfCPRZyenEcw9q8hODIktyv94aVfs2SEZ4uXI9ZcLMylYIo0KyxTdAgnxo54URRqC/ULvqblQCSRrreYCQgDFfgH75LylcwW8ocweFqjhfPOfmbkxvOyPTdVD/HlgRLUlrP359Y/ReM7RIMiJXuwXiHwTGiWM5CXrqG8VvGh6NQ==',
	'MIGIAkIBfjlrLg+NxLm117ZMUkWscyjBhTOzAOKJbVOvTvFtaz6ernWK0I8TwtVQICIdvbFYf9KiXOUX8+6tN1rFOxSPKgoCQgFwXLLCZmvBjJSVT7w4WQsMQMf+zc+frlvw7yNZTnGkXKLK+31Skxmxs2G8EPu81eZtgDyhs6Z8ZvkKnpiJLx3ErQ==',
	'MIGHAkE7CV49+JlopkKsr6niAuRhfllc6mfXVXI96kWR69ixrV2FiL2Fob6oYUpr4Zbg2ApJIl6B1VRGQa5816cPX/44CQJCAReT9dMol2AqByOs+Z8Z5bldg6eB1zfS1S1Zd7NhOaFWKZQQAfEaf3xTGGVrdOUYZEa5dn1+zOKhzs0lwgJOGyBd',
	'MIGHAkFYJGsSVYLC8QbLDEhSnrOCYJAMYbQrT2/tEsK3lljQphZxmePX6v+5aAJGAxQfS4CBi11iOzxRWCF8azRyPKY1lAJCAM3gG5myvcaCD5SRzp6OCKeHDcP2nhgSkNyQTHPtbvewl98H6afxwI9j+BhnYnP6Bmuv8etPUQ9ZnLmYJW2bjPDJ',
	'MIGHAkIBBh75cL9mCcIsKfQyMiRPoC49UAOyy6R0nN4FWvCcU7rILKcSoiGs5u+RGdMqqK3cgUoBFeJoaZZdQ//wy7HOEVoCQVwa3MYQFz/vDt6h/qOvv1xVnt8Tnf05Jg2coeOwi5tBKGI28WZEEVWnDYoEZx06zwT0d1OIf2htgnMCslQl9eMb',
	'MIGIAkIBWgCrqYPLk0gwCMvM3wATP59puYFbdKYx+Oqg8wooHstul9fomg32fu0jX2VnmIDEgbNDiuzM85yTVib9jp1fbbQCQgEY2Itoh1Vd4YW91d9PM3MaxoE1/IIe9bv530cdMrDQ8l4QBoK/lu5NeRg7rKFAUqy9keMtbnD2RUlZht7JkMaMrw==',
	'MIGHAkEU37StZUptp1zN/JamuTVzIzQdirYNOAxyWJXCAM/aIsW4dCeqbxehrAKmwqHXJtRCi2dLEw8WW0jWoSXpV2PSLwJCAIiUJENQiM+T3elRQyFl00UceSMGtsNZH9iO71NlXgkW2VgPTeQBmBC9hc+/0b/fbQeqb7HlYEJK3lLg+yyCKViX',
	'MIGIAkIAr0cMsA5hlcWHIkh+91NVWu1ZjYs8P/DQslgv1h+X0sCXFtF2vGJ1mg7mM0aByu7kX1q84kuQG0q7akdQd2+NwloCQgH4J6cTH447KGJVQfKcX/9RrxPtjDs+HBhC7uT6xsCLzSo8Db4ssMDtopCM9xAtdXcAJzbDeOhP23/yQrUNUE0c4A==',
	'MIGIAkIArMaGOAoclY5lWOUj99ptRhPeMNf3Iag/UQsabnDO8XecnFAydn4FEw9gPFmW537xfmlorXl4l74UCexbxJFs7YMCQgEEELCj+M2zwSm+rB+z9kTjWw3tvzN86HvZ5rl1eZKYFBdC5aqHlmVeJwd30I8W0ZUas57nhyePXpt7mpfzEwgw/g==',
	'MIGHAkFaWhXew0fa5s1Sty/C21y/smtcsPUOIiVcsS6H/7YVhiNfmt8/s+eSkw6IilVIDNVDTojBCj/DHhIRIlXzzZU7twJCANm8LLSeUf2kfD+bK72/VBzAiwyMaW3XkVWplGn1vZePvUXt8ZWYN3if4Um/W7dvGMyzIXe0uBPRFHWuqvZfToRK',
	'MIGHAkEnovh2GLEQmZOYd4bSZ255a/Rjf/5bdidCqjRBRKEnty57Utda6jEgtf3iaL8WvL4x4vZuM3Ou4nCdTwEehePZ1QJCANaTAnl/r1iaLYlPxzXVKFq3gbO9rtRTj1ggB2Tx+03tPcBMSQX36iZwnbM1480SaUuxEkPANO7gbZ6DiptB8acA',
	'MIGHAkFEfkUAO34U01uWzl7O5kWx1MuNthhIkxeF2oqvzuukMRaGn9Pr04orS4VqGfyalf8boHLd4LXW9vpjGZ0Krek65QJCAatpiEn+7XunSspbdahmJ5KpzfAGN5xrPBc5w/ZKwdL0MsClYovA8XVyb7hatL9JDlZOyvLN5Z8w0eaWadFLhkBY',
	'MIGHAkIA35XueY8H439dUNl/5AVvtscBaI0jDa/0di7y4QCwqaGqbFMgVPpcra6CFWwzGnxUMa8MF7KU1CAvXoZk6XXVrTUCQVevzx4ukhvIfYHntamEHuDw/H+I7rGcI/SOblNcJoYPUo12U/ah6PzS48Gzxwy6yMm2kROEZvqmZdebAio9GPdF',
	'MIGIAkIBYN4bbGzukqCb6oqXEmDkqLkUox4ogjdL9YT7xsUNnPtlHVZzjQ3CdUF4I56CakFiTsqMkx+eshuzat1yyjIpqz4CQgHwb+kESkdB/dD6GO1TViKRrBJ70k/CMDnetFO8fBnP6zZ0ofvnUKM/iVzPBjaJ4lblIKA3io2IO6YwzmXT0kqLPg==',
	'MIGIAkIAiJSsX/H3EOvaXNEh7mg7GxGKinShFwvD+01dNtzUBmH6sR1o4cc+m9ZEb1099kRfUphUCvBmcusKTBzeZ/Ru6zgCQgCjo4+Pv33e0q2c67O41G5c5HT6BUPutLMzhAZoPi193FQSlR5DKSfVuKMDEeZ/1cM+CJGm5rPoFlRD9ZV2HDWITg==',
	'MIGIAkIBHVvUpaV0AZbuSGII42Jz/1TjDsHzlec2WRvui3uvkJGcu9IeY9IIObWOa+2LMYWQGYxLg1+H/mK4DdGwrKxWhCoCQgCAG03LvxRFk3TaLMCd7VhoTBS51kLO3uTmOWK2rs9Y36DXzmH+zI+5DSbYE14BtA14HkUf0+UplZpausY0CAH7ag==',
	'MIGGAkFLzHH2j6aVHzWWcWJlGpXgP5Nq3fY95uxjhMje835W3dfhVaNp1lNroEh4fDNskUg7aeri9SYHvN5GZKk6nZ97IQJBBibs5g8Fa2xbT8f23TbHQ89wyYD+MPJ/97VzW8maBnqqV+kN4xlptRvTjRBUeb8umt1ol3kKfEI7AEjd46SiYyA=',
	'MIGHAkE3aAEKHrCfu+dhBYdvmU0ffbHf1i0y7jivVoNJbfzOq+Ov71gNuR+FGq2ZiZPiHxBZSZZcgMEtEgj456q9tWjLFgJCAISxkWFlD2L+ZSBAS984aFNp2GGFP/H89oELlO+ISSV6R+yKTmFIfAzzmvGMf3M6kqXTSFcYLc7v5UgtdxOci7sl',
	'MIGHAkIA4OKNqgtrUadUFGMF0FNeGMfkDFfaSNh/qxgtnV2xHZPuGtece+LQFOwvNJHSSWAAydEFJLxoyRSBA2qcWfiWuxYCQVYvqt/xsYbQexn+hDHEAOJwS0yzlbXLcx3mt0E8dk0TlPFNSZIO1G11dfvp5rNYKuzT4kuBTk77uN+X1zUZ116U',
	'MIGIAkIB9AQl689ID1y1nkKphhSLY5MuKeMWe3Y6le7fu3coccIp+bnxm2h4/vw4mc75QmeEYYRCds2vWwO27WvRBlJYFlkCQgDrGSp/AgdcMkoDBQOhVFHU8gzWdGOT2SwxSnjj4ETK6EJ8EVkc7UnSNvEmTP/xos48tqomEfR18xT86bisAVUQZA==',
	'MIGIAkIBGbuv+Le9Qj4h5pxTyAoOJ61++6prB1PpRaRwkRuTYVPCDU4HD1f2Ci1DavP7UKIJy3dWdCFbwDrQfMCbsKrKt+wCQgEFU1gyNqPhbDJ96vNV6wB9w8Y84/OsP7EPmeU570jdq5mncnt46IM/0yKnZikd8WlSBIruNuyoqkL0M4FsRLXlSQ==',
	'MIGHAkIBcdIBy03H6MiPavf/RPD4tn81hypZa1wELGd1/y7MAQW1rdyQDVW9bacaJnZsPMUrY1DVzxr4nDSX96oUXLk/LsECQRhsw1Luz/vaYC06urw+5lSK9UNMYQbL+/zjLNayE3okR/uEWsy5DguvqpJI22B5B28/VQO1dwrwZtX7rAEYZmd/',
	'MIGHAkEHp/2OMo1PKEdchtpkWSEMj1dxPMLmKa169CinzbnAJn72nSrMGr2Uta2f9FEDk5dHg9wJS6DRMp2VX2QOWqOBbAJCAJyH+XFhmSM6+O9uR/c5zo6Q8E6gdBnyFQhF2rS9i6E2HG7RyNvsjfiv/EJLGRaSvxGlaLnT0HOYUi6tiVv1L3UP',
	'MIGHAkIBBgkS3ybxsfv1mmaOwf9E44QurEr6d2Bu6wyvKzKLpCCoHLswt0cfIIu2Oug6kkZ7RSSmlO6l4Mk2JCjsprIvO4ACQRjWttb6PcyrelXX4cA0NdHukFSWNEvKVfxuTjYbOJ6vi6zoeNmBTk1ixHglhoMkaOYZOAh5ju4x1gfy9pyMgEqK',
];


private const theEncGoCertPEM = '
-----BEGIN CERTIFICATE-----
MIIEQzCCA6SgAwIBAgIhAOyMzab64UoSy1Fpq2CUWqonSvJ6fiiYmnD5Xd5RePs2
MAoGCCqGSM49BAMEMIHdMQswCQYDVQQGEwJVSzEPMA0GA1UECBMGTG9uZG9uMQ8w
DQYDVQQHEwZMb25kb24xHDAaBgNVBAkTE1N0cmVldCBOb05hbWUgbm8uMDAxETAP
BgNVBBETCFpFUk8wMDAwMQ8wDQYDVQQKEwZnb2xhbmcxEjAQBgNVBAMTCWxvY2Fs
aG9zdDFWMFQGA1UEBRNNMTY4NTIwMDM5Njg2NzUwOTU1ODM5MjEzMzc2NjU4OTk3
MjQ1OTg0MzY0ODk3NDU5MzAyNDMyMjM3MTY1MzAxMTM1ODI3MjQ0OTgxMTcwIBcN
MjYwMTE0MDgxNTE5WhgPMjEyNjAxMTQwODE1MjBaMIHkMQswCQYDVQQGEwJSTzEN
MAsGA1UECBMEQ2x1ajEUMBIGA1UEBxMLQ2x1ai1OYXBvY2ExGDAWBgNVBAkTD05v
IFN0cmVldCBuby4wMDENMAsGA1UEERMEMzQwMDEQMA4GA1UEChMHc21hcnRnbzEc
MBoGA1UEAwwTd2VibWFzdGVyQGxvY2FsaG9zdDFXMFUGA1UEBRNOMTA2OTk0NjEw
MjE0NDkyODMxODU0NzA1ODU3NzIyMjk2MDE2MjA2NDQzNjg4MDc0OTc2NjYxNTMw
MzkwMzk1OTQ1NjU5MjY1NTE0Mjk0MIGbMBAGByqGSM49AgEGBSuBBAAjA4GGAAQA
Zu2+4QAYdmXIkx5aEqB01Ar9n7XGzoDBpxI+di1ppLcbStpYxWC9jdvWBOnd2ay/
GwrcbxpF6+dhOHzRPAJuq20AQ8uguHQIoCNycl7G5mFNhNDtWht6Q8qPYwotCWX6
mIodVVPtWhqVD8L6Md5BbUBQtRb4wDZOuW+wIoNBDQCbN9WjgeYwgeMwDgYDVR0P
AQH/BAQDAgP4MDsGA1UdJQQ0MDIGCCsGAQUFBwMEBggrBgEFBQcDAwYIKwYBBQUH
AwgGCCsGAQUFBwMBBggrBgEFBQcDAjAMBgNVHRMBAf8EAjAAMDMGA1UdDgQsBCow
MUtBMDhYMVNQWlRQM1RLQi1NOTZQUUFZUUhDS1ZLLTc4ODE4MzkwODMwNQYDVR0j
BC4wLIAqMDFLQTA4WDFTUExXTVBXNVEtOUYySDBOSVZJT0hPNC04MTE4Njk2ODg5
MBoGA1UdEQQTMBGCCWxvY2FsaG9zdIcEfwAAATAKBggqhkjOPQQDBAOBjAAwgYgC
QgEuJnDYf6WLk/qt1mnIqxLv/bqfCA4rh9jMLSvE0b+MYJLHBTVeI2N0hwZMH5tv
A8xECBPYr1jJN1Vfc9IeXlqN1QJCAUYxVtWuM66cX6rao0la7I0Td4m1DuNBKhOl
3i91FKdQ2gW4NkdcCSIVEMWMzAoedn9TaSmIv+g2mp+gkHuOxEVC
-----END CERTIFICATE-----
';

private const theEncGoPrivkeyPEM = '
-----BEGIN PRIVATE KEY-----
Proc-Type: 4,ENCRYPTED
DEK-Info: AES-256-CBC,763a0cd2bc16bba1ecdb7f08d969c612

Wabmv+HkHK6csWbMf++0Om/X7yOp/TXN2lhYQcO7Twfnht8/NcQGAWPCczexIE4w
DA5y/q7d4cZi2+lD3osvzVYz3Vyi74iA1BGBYQap3gRiJM/ckrueP5P89QbyNid9
fAke7lchk3hV0hVCKqpcDa1zAdVgtYMl6Dd6fAS0YkkGg0QBWqt7U75RK1A0CCoJ
+oVfWmrrT5IqKQCPBVECqZ+eAqJtaHjqPofEKtz3zQxjAM6FsctkFn/L43KqlfdH
ArF+qlfwpmWMyA8ZlAlT+6Pi83o2CcGE1KZ80Ubz5Rl3DrTAa1URUokB0CgBuYZu
Dy9V8MsSWDpfQx2uPHrzAw==
-----END PRIVATE KEY-----
';

private const theEncGoPubkeyPEM = '
-----BEGIN PUBLIC KEY-----
MIGbMBAGByqGSM49AgEGBSuBBAAjA4GGAAQAZu2+4QAYdmXIkx5aEqB01Ar9n7XG
zoDBpxI+di1ppLcbStpYxWC9jdvWBOnd2ay/GwrcbxpF6+dhOHzRPAJuq20AQ8ug
uHQIoCNycl7G5mFNhNDtWht6Q8qPYwotCWX6mIodVVPtWhqVD8L6Md5BbUBQtRb4
wDZOuW+wIoNBDQCbN9U=
-----END PUBLIC KEY-----
';

private const theEncGoSignatures = [ // non-ASN1 signatures
	'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',
];

private const theEncPrivKeyPass = 'pass:my'; // {{{SYNC-WITH-GOLANG-TESTS-CREATE-X509}}}


	final public function Initialize() {
		//--
		return true;
		//--
	} //END FUNCTION


	final public function ShutDown() {
		//--
		// do nothing
		//--
	} //END FUNCTION


	final public function Run() {

		//-- dissalow run this sample if not test mode enabled
		if(!defined('SMART_FRAMEWORK_TEST_MODE') OR (SMART_FRAMEWORK_TEST_MODE !== true)) {
			$this->PageViewSetErrorStatus(503, 'ERROR: Test mode is disabled ...');
			return;
		} //end if
		//--

		if(SmartCryptoEcdsaOpenSSL::isAvailable() !== true) {
			$this->PageViewSetErrorStatus(503, 'WARNING: OpenSSL EcDSA is N/A ...');
			return;
		} //end if

		$this->PageViewSetCfg('rawpage', true);
		$this->PageViewSetCfg('rawmime', 'text/plain');
		$this->PageViewSetCfg('rawdisp', 'inline; filename="sample-ecdsa.txt"');

		//--

		$usePassPhrase = (string) trim((string)$this->RequestVarGet('encryptedprivkey', '', 'string'));
		$nonASN1mode   = (string) trim((string)$this->RequestVarGet('nonasn1', '', 'string'));

		$pKeyPassPhrase = (string) self::theEncPrivKeyPass;
		if((string)$usePassPhrase == 'no') {
			$pKeyPassPhrase = null;
		} //end if

		$useASN1 = true;
		if((string)$nonASN1mode == 'yes') {
			$useASN1 = false;
		} //end if
		//--

		$main = [];
		$main[] = 'EcDSA Tests: Curve / Algo = '.SmartCryptoEcdsaOpenSSL::OPENSSL_ECDSA_DEF_CURVE.' / '.SmartCryptoEcdsaOpenSSL::OPENSSL_CSR_DEF_ALGO.' ; Sign and Verify Algo = '.SmartCryptoEcdsaOpenSSL::OPENSSL_SIGN_DEF_ALGO;

		//-- php

		$arrCerts = (array) SmartCryptoEcdsaOpenSSL::newCertificate(
			['commonName' => 'My Sample Name', 'emailAddress' => 'my@email.local', 'organizationName' => 'my.local', 'organizationalUnitName' => 'My Sample Test - ECDSA Digital Signature'],
			1, // years
			(string) SmartCryptoEcdsaOpenSSL::OPENSSL_ECDSA_DEF_CURVE,
			(string) SmartCryptoEcdsaOpenSSL::OPENSSL_CSR_DEF_ALGO,
			$pKeyPassPhrase // do not cast to string, can be null
		);
		if((string)$arrCerts['err'] != '') {
			$this->PageViewSetCfg('error', 'New Certificate Error: '.$arrCerts['err']);
			return 500;
		} //end if
		$main[] = (string) 'New Certificate and Key Pair: '.SmartUtils::pretty_print_var($arrCerts);

		$dataMsg = 'A message to Sign'; // {{{SYNC-SIGN-MSG-GO-PHP}}}
		$main[] = 'Data to Sign/Verify: `'.$dataMsg.'`';

		$exportSigns = []; // for go ...
		for($i=0; $i<50; $i++) {

			$arrSign = (array) SmartCryptoEcdsaOpenSSL::signData(
				(string) ($arrCerts['privKey'] ?? null),
				(string) ($arrCerts['pubKey'] ?? null),
				(string) $dataMsg,
				(string) SmartCryptoEcdsaOpenSSL::OPENSSL_SIGN_DEF_ALGO,
				$pKeyPassPhrase, // do not cast to string, can be null
				(bool)   $useASN1
			);
			if((string)$arrSign['err'] != '') {
				$this->PageViewSetCfg('error', 'Sign #'.$i.' Error: '.$arrSign['err']);
				return 500;
			} //end if
			$main[] = (string) 'Sign #'.$i.' Data: '.SmartUtils::pretty_print_var($arrSign);

			$arrVfy = (array) SmartCryptoEcdsaOpenSSL::verifySignedData(
				(string) ($arrCerts['pubKey'] ?? null),
				(string) $dataMsg,
				(string) ($arrSign['signatureB64'] ?? null),
				(string) SmartCryptoEcdsaOpenSSL::OPENSSL_SIGN_DEF_ALGO,
				(bool)   $useASN1
			);
			if(((string)$arrVfy['err'] != '') OR (($arrVfy['verifyResult'] ?? null) !== true)) {
				$this->PageViewSetCfg('error', 'Verify Sign #'.$i.' Error: '.$arrVfy['err'].' # '.($arrVfy['verifyResult'] ?? null));
				return 500;
			} //end if
			$main[] = (string) 'Verify Signed #'.$i.' Data: '.SmartUtils::pretty_print_var($arrVfy);

			$exportSigns[] = (string) ($arrSign['signatureB64'] ?? null);

		} //end for
	//	$main[] = (string) Smart::json_encode((array)$exportSigns, true, true, false); // used for go export

		//-- go plain

		$arrGoSign = (array) SmartCryptoEcdsaOpenSSL::signData(
			(string) trim((string)self::theGoPrivkeyPEM),
			(string) trim((string)self::theGoPubkeyPEM),
			(string) $dataMsg,
			(string) SmartCryptoEcdsaOpenSSL::OPENSSL_SIGN_DEF_ALGO,
		);
		if((string)$arrGoSign['err'] != '') {
			$this->PageViewSetCfg('error', 'Sign Go Error: '.$arrGoSign['err']);
			return 500;
		} //end if
		$main[] = (string) 'Sign Go Data: '.SmartUtils::pretty_print_var($arrGoSign);

		$arrGoVfy = (array) SmartCryptoEcdsaOpenSSL::verifySignedData(
			(string) trim((string)self::theGoPubkeyPEM),
			(string) $dataMsg,
			(string) ($arrGoSign['signatureB64'] ?? null),
			(string) SmartCryptoEcdsaOpenSSL::OPENSSL_SIGN_DEF_ALGO
		);
		if(((string)$arrGoVfy['err'] != '') OR (($arrGoVfy['verifyResult'] ?? null) !== true)) {
			$this->PageViewSetCfg('error', 'Verify Sign Go Error: '.$arrGoVfy['err'].' # '.($arrGoVfy['verifyResult'] ?? null));
			return 500;
		} //end if
		$main[] = (string) 'Verify Go Data: '.SmartUtils::pretty_print_var($arrGoVfy);
		for($i=0; $i<count(self::theGoSignatures); $i++) {
			$main[] = 'Go Signature of Data: '.trim((string)self::theGoSignatures[$i]);
			$arrGoSignVfy = (array) SmartCryptoEcdsaOpenSSL::verifySignedData(
				(string) trim((string)self::theGoPubkeyPEM),
				(string) $dataMsg,
				(string) trim((string)self::theGoSignatures[$i]),
				(string) SmartCryptoEcdsaOpenSSL::OPENSSL_SIGN_DEF_ALGO
			);
			if(((string)$arrGoSignVfy['err'] != '') OR (($arrGoSignVfy['verifyResult'] ?? null) !== true)) {
				$this->PageViewSetCfg('error', 'Verify Sign #'.$i.' Go Error: '.$arrGoSignVfy['err'].' # '.($arrGoSignVfy['verifyResult'] ?? null));
				return 500;
			} //end if
			$main[] = (string) 'Verify Go Signed #'.$i.' Data: '.SmartUtils::pretty_print_var($arrGoSignVfy);
		} //end for

		//-- go enc

		$main[] = (string) 'Go Original Encrypted PrivKey: '."\n".SmartUtils::pretty_print_var(trim((string)self::theEncGoPrivkeyPEM))."\n";
		$decryptedGoPrivKeyArr = (array) SmartCryptoEcdsaOpenSSL::decryptPrivateKeyPem(
			(string) trim((string)self::theEncGoPrivkeyPEM),
			(string) self::theEncPrivKeyPass
		);
		if((string)$decryptedGoPrivKeyArr['err'] != '') {
			$this->PageViewSetCfg('error', 'Go Enc Decrypted PrivKey Error: '.$decryptedGoPrivKeyArr['err']);
			return 500;
		} //end if
		$main[] = (string) 'Go Enc Decrypted PrivKey: '.SmartUtils::pretty_print_var($decryptedGoPrivKeyArr);

		$reEncryptedGoPrivKeyArr = (array) SmartCryptoEcdsaOpenSSL::encryptPrivateKeyPem(
			(string) ($decryptedGoPrivKeyArr['privKey'] ?? null),
			(string) self::theEncPrivKeyPass
		);
		if((string)$reEncryptedGoPrivKeyArr['err'] != '') {
			$this->PageViewSetCfg('error', 'Go Enc Re-Encrypted PrivKey Error: '.$reEncryptedGoPrivKeyArr['err']);
			return 500;
		} //end if
		$main[] = (string) 'Go Enc Re-Encrypted PrivKey: '.SmartUtils::pretty_print_var($reEncryptedGoPrivKeyArr);

		$arrGoEncSign = (array) SmartCryptoEcdsaOpenSSL::signData(
			(string) trim((string)self::theEncGoPrivkeyPEM),
			(string) trim((string)self::theEncGoPubkeyPEM),
			(string) $dataMsg,
			(string) SmartCryptoEcdsaOpenSSL::OPENSSL_SIGN_DEF_ALGO,
			(string) self::theEncPrivKeyPass
		);
		if((string)$arrGoEncSign['err'] != '') {
			$this->PageViewSetCfg('error', 'Enc Sign Go Error: '.$arrGoEncSign['err']);
			return 500;
		} //end if
		$main[] = (string) 'Enc Sign Go Data: '.SmartUtils::pretty_print_var($arrGoEncSign);

		$arrGoEncVfy = (array) SmartCryptoEcdsaOpenSSL::verifySignedData(
			(string) trim((string)self::theEncGoPubkeyPEM),
			(string) $dataMsg,
			(string) ($arrGoEncSign['signatureB64'] ?? null),
			(string) SmartCryptoEcdsaOpenSSL::OPENSSL_SIGN_DEF_ALGO
		);
		if(((string)$arrGoEncVfy['err'] != '') OR (($arrGoEncVfy['verifyResult'] ?? null) !== true)) {
			$this->PageViewSetCfg('error', 'Enc Verify Sign Go Error: '.$arrGoEncVfy['err'].' # '.($arrGoEncVfy['verifyResult'] ?? null));
			return 500;
		} //end if
		$main[] = (string) 'Enc Verify Go Data: '.SmartUtils::pretty_print_var($arrGoEncVfy);

		for($i=0; $i<count(self::theEncGoSignatures); $i++) {
			$main[] = 'Go Enc Signature of Data: '.trim((string)self::theEncGoSignatures[$i]);
			$arrGoEncSignVfy = (array) SmartCryptoEcdsaOpenSSL::verifySignedData(
				(string) trim((string)self::theEncGoPubkeyPEM),
				(string) $dataMsg,
				(string) trim((string)self::theEncGoSignatures[$i]),
				(string) SmartCryptoEcdsaOpenSSL::OPENSSL_SIGN_DEF_ALGO,
				false // nonASN1 mode
			);
			if(((string)$arrGoEncSignVfy['err'] != '') OR (($arrGoEncSignVfy['verifyResult'] ?? null) !== true)) {
				$this->PageViewSetCfg('error', 'Enc Verify Sign #'.$i.' Go Error: '.$arrGoEncSignVfy['err'].' # '.($arrGoEncSignVfy['verifyResult'] ?? null));
				return 500;
			} //end if
			$main[] = (string) 'Verify Go Enc Signed #'.$i.' Data: '.SmartUtils::pretty_print_var($arrGoEncSignVfy);
		} //end if

		//--

		$this->PageViewSetVar(
			'main',
			(string) implode("\n\n", (array)$main)
		);

		//--

	} //END FUNCTION


} //END CLASS


/**
 * Index Controller
 *
 * @ignore
 *
 */
final class SmartAppIndexController extends SmartAppAbstractController {} //END CLASS


/**
 * Admin Controller
 *
 * @ignore
 *
 */
final class SmartAppAdminController extends SmartAppAbstractController {} //END CLASS


/**
 * Task Controller
 *
 * @ignore
 *
 */
final class SmartAppTaskController extends SmartAppAbstractController {} //END CLASS


// end of php code
