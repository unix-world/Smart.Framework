# Smart.Framework: a practical, modern and high performance PHP / JavaScript Framework for Web featuring Middlewares + MVC
## Dual-licensed: under BSD license or GPLv3 license (at your choice)
### This software project is open source.
#### You must choose which license to use depending on your use case: BSD license or GPLv3 license
**(c) 2008 - present unix-world.org** / support&#064;unix-world.org

| &nbsp; | &nbsp; |
| ------------- | ------------- |
| **Demo URL:** | [http://demo.unix-world.org/smart-framework/](http://demo.unix-world.org/smart-framework/) |
| **Download URL:** | [https://github.com/unix-world/Smart.Framework](https://github.com/unix-world/Smart.Framework) |
| **Download Modules URL:** | [https://github.com/unix-world/Smart.Framework.Modules](https://github.com/unix-world/Smart.Framework.Modules) |

&nbsp;

### Smart.Framework design philosophy:
- A very **pragmatic** and practical aproach: **A Practical Web Framework for Practical People**
- Based on a previous **experience of more than 17 years** of developing web projects, research and experiments using web technologies
- Web oriented approach: to offer **a solid and secure platform** for building websites or web based applications for Web Clients, Desktops and Mobiles
- Clean Code: **MVC code pattern** with built-in Dependency-Injection
- **Hybrid** Architecture: **Multi-Tier combined with Middlewares architecture** to provide a flexible and responsive web service
- **Modular Architecture**: **support creating reusable modules** (there are also many turn-key modules available in Smart.Framework.Modules)
- **Full Decoupled Libraries**: the framework core is using independent (decoupled) libraries (**no 3rd party dependencies**)
- **NameSpace Separation in modules** for: Models, Views, Controllers and Libraries
- **Easy to integrate** with 3rd party (vendor) libraries
- Native **Cloud Server Services (built-in)**, as module for: WebDAV Server, CalDAV Server, CardDAV Server
- Native **Cloud Client Provider (built-in)**, as library for HTTP/HTTPS access which supports the full range of HTTP(S) Methods / Requests: GET, POST, PUT, DELETE, ...
- **Native Router** based on smart URL Links: **/?page=my-module.sample** that can be used as **/?/page/my-module.sample** or **/?/page/sample** if (my-module is default bundle)
- Integrates with **Apache Rewrite** to use SEO friendly links like **/sample.html** instead of traditional link **/?page=my-module.sample** or smart link **/?/page/my-module.sample/**

#### Easy develop your web projects with Smart.Framework
**The primary goal of Smart.Framework is to provide a very practical, fast and secure web framework.**
Following this philosophy Smart.Framework provides an optimal balance between acceptable coding skills and performance delivered.

It is a **lightweight but feature reach** PHP / JavaScript web framework, **mature and stable**, it is being **proactively used and tested** in several high-end web projects **that can really serve many millions of page views per month with a single physical server** !
The original software architecture of this web framework allows it to deliver a paradox:
* it have more default built-in features in the code base than the well-known frameworks compared with CodeIgniter, Symfony or Laravel
* it delivers much more performance being between 1.5x to 4x faster (as HTTP Requests / second) compared with CodeIgniter, Symfony or Laravel
* when used with Persistent Cache based on In-Memory DB like Redis or Memcache it beats Varnish in many aspects:
	- delivers ~ the same speed as Varnish but allows granulary level caching policy of zones in controllers
	- works also with HTTPS (by example, Varnish does not)
	- caching policies can be controlled to expire based on content / GET or POST variables INPUT even with changing COOKIES


#### This software framework is compatible, stable and actively tested with PHP 7.4 / 8.0 / 8.1 / 8.2 / 8.3 / 8.4 versions.
**Prefered PHP version** is: **8.2** (LTS).

&nbsp;
### Benchmark Scenario:
**Using a simple controller (no Caching) that Outputs: 'Hello World'**
The benchmark was running using **Apache Benchmark** suite with the following command:
`ab -n 5000 -c 250 http://{localhost}/{framework}/{benchmark-page}`
&nbsp;
**Hardware platform**: one physical server (Supermicro):
+ **2 x Intel(R) Xeon(R) CPU E5-2699 v4** @ 2.20GHz 64-bit (Total: 44 cores / 88 threads)
+ **512 GB RAM**, DDR4 ECC @ 2133 MHz
+ **HDD 2 x 1TB** SSD/NVME

**Software**:
+ OS: **Debian 12 Linux 64-bit**, up-to-date
+ Apps: **Apache 2.4.62**, **PHP 8.2.26 with Opcache enabled**

#### Benchmark Results of tested PHP Frameworks:
+ **Smart.Framework v.8.7 head@2024.12.16** with MarkersTPL Templating: ~ **8.878K** (8878) requests per second
+ **CodeIgniter v.4.5** with PHP Templating: ~ **5.864K** (5864) requests per second ( **1.5x slower than Smart.Framework** )
+ **Symfony 7.2** with Twig Templating: ~ **2.763K** (2763) requests per second ( **3x slower than Smart.Framework** )
+ **Laravel 11.4** with Blade Templating: ~ **2.214K** (2214) requests per second ( **4x slower than Smart.Framework** )
