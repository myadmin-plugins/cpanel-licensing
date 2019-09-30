# Cpanel Licensing Class

Cpanel Licensing Class


## New Cpanel Licenses Type Update

The CPanel licenses are undergoing as major change heres wwhats left on the work to migrate to the new setup


### Potential Issues and thigns to address

* If there is a commmunication problem loading the license data from cpanel ensure that it does not set some crazy values; but instead bails or retries
* For reactivation of a license, it would go by the last known / billed invoice amount right or would the cost for reactivation be reset back to the minimmum amounts where we assume no accounts? 


### Full New Package Offering

This is the complete list of licenses we can order including both old and new ones.

* P1814 INTERSERVER-INTERNAL-VPS
* P188 INTERSERVER-INTERNAL-VZZO
* P21159 cPanel Admin Cloud
* P21163 cPanel Pro Cloud
* P21167 cPanel Premier Cloud
* P21171 cPanel Premier Metal
* P21175 cPanel Admin Cloud (Distributor)
* P21179 cPanel Pro Cloud (Distributor)
* P21183 cPanel Premier Cloud (Distributor)
* P21187 cPanel Premier Metal (Distributor)
* P21893 cPanel Autoscale (Internal)
* P21897 cPanel Autoscale (External)
* P22081 cPanel Plus Cloud
* P2466 INTERSERVER-ENKOMP-INTERNAL-A500
* P2470 INTERSERVER-ENKOMP-EXTERNAL-A500
* P24765 DNSNODECLOUD
* P24769 DNSNODEMETAL
* P24925 cPanel Premier Cloud 150
* P24929 cPanel Premier Cloud 200
* P24933 cPanel Premier Cloud 250
* P24937 cPanel Premier Cloud 300
* P24941 cPanel Premier Cloud 350
* P24945 cPanel Premier Cloud 400
* P24949 cPanel Premier Cloud 450
* P24953 cPanel Premier Cloud 500
* P24957 cPanel Premier Cloud 550
* P24961 cPanel Premier Cloud 600
* P24965 cPanel Premier Cloud 650
* P24969 cPanel Premier Cloud 700
* P24973 cPanel Premier Cloud 750
* P24977 cPanel Premier Cloud 800
* P24981 cPanel Premier Cloud 850
* P24985 cPanel Premier Cloud 900
* P24989 cPanel Premier Cloud 950
* P24993 cPanel Premier Cloud 1000
* P25277 cPanel Premier Metal 150
* P25281 cPanel Premier Metal 200
* P25285 cPanel Premier Metal 250
* P25289 cPanel Premier Metal 300
* P25293 cPanel Premier Metal 350
* P25297 cPanel Premier Metal 400
* P25301 cPanel Premier Metal 450
* P25305 cPanel Premier Metal 500
* P25309 cPanel Premier Metal 550
* P25313 cPanel Premier Metal 600
* P25317 cPanel Premier Metal 650
* P25321 cPanel Premier Metal 700
* P25325 cPanel Premier Metal 750
* P25329 cPanel Premier Metal 800
* P25333 cPanel Premier Metal 850
* P25337 cPanel Premier Metal 900
* P25341 cPanel Premier Metal 950
* P25345 cPanel Premier Metal 1000
* P29017 cPanel Premier Cloud 100
* P29021 cPanel Premier Metal 100
* P401 INTERSERVER-EXTERNAL
* P559 INTERSERVER-EXTERNAL-VPS
* P560 INTERSERVER-EXTERNAL-VZZO
* P576 INTERSERVER-INTERNAL


### Current/Old Packages

We currently use these packages (which will be getting changed intot he new ones)

* P1814 INTERSERVER-INTERNAL-VPS
* P188 INTERSERVER-INTERNAL-VZZO
* P576 INTERSERVER-INTERNAL
* P559 INTERSERVER-EXTERNAL-VPS
* P560 INTERSERVER-EXTERNAL-VZZO
* P401 INTERSERVER-EXTERNAL


## New Packages

These are the packages we'll be using and vonerting all the existing packages to

* P21893 cPanel Autoscale (Internal)
* P21897 cPanel Autoscale (External)


https://tldr.ostera.io/


## Build Status and Code Analysis

Site          | Status
--------------|---------------------------
![Travis-CI](http://i.is.cc/storage/GYd75qN.png "Travis-CI")     | [![Build Status](https://travis-ci.org/detain/myadmin-cpanel-licensing.svg?branch=master)](https://travis-ci.org/detain/myadmin-cpanel-licensing)
![CodeClimate](http://i.is.cc/storage/GYlageh.png "CodeClimate")  | [![Code Climate](https://codeclimate.com/github/detain/myadmin-cpanel-licensing/badges/gpa.svg)](https://codeclimate.com/github/detain/myadmin-cpanel-licensing) [![Test Coverage](https://codeclimate.com/github/detain/myadmin-cpanel-licensing/badges/coverage.svg)](https://codeclimate.com/github/detain/myadmin-cpanel-licensing/coverage) [![Issue Count](https://codeclimate.com/github/detain/myadmin-cpanel-licensing/badges/issue_count.svg)](https://codeclimate.com/github/detain/myadmin-cpanel-licensing)
![Scrutinizer](http://i.is.cc/storage/GYeUnux.png "Scrutinizer")   | [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/myadmin-plugins/cpanel-licensing/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/myadmin-plugins/cpanel-licensing/?branch=master) [![Code Coverage](https://scrutinizer-ci.com/g/myadmin-plugins/cpanel-licensing/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/myadmin-plugins/cpanel-licensing/?branch=master) [![Build Status](https://scrutinizer-ci.com/g/myadmin-plugins/cpanel-licensing/badges/build.png?b=master)](https://scrutinizer-ci.com/g/myadmin-plugins/cpanel-licensing/build-status/master)
![Codacy](http://i.is.cc/storage/GYi66Cx.png "Codacy")        | [![Codacy Badge](https://api.codacy.com/project/badge/Grade/226251fc068f4fd5b4b4ef9a40011d06)](https://www.codacy.com/app/detain/myadmin-cpanel-licensing) [![Codacy Badge](https://api.codacy.com/project/badge/Coverage/25fa74eb74c947bf969602fcfe87e349)](https://www.codacy.com/app/detain/myadmin-cpanel-licensing?utm_source=github.com&utm_medium=referral&utm_content=detain/myadmin-cpanel-licensing&utm_campaign=Badge_Coverage)
![Coveralls](http://i.is.cc/storage/GYjNSim.png "Coveralls")    | [![Coverage Status](https://coveralls.io/repos/github/detain/db_abstraction/badge.svg?branch=master)](https://coveralls.io/github/detain/myadmin-cpanel-licensing?branch=master)
![Packagist](http://i.is.cc/storage/GYacBEX.png "Packagist")     | [![Latest Stable Version](https://poser.pugx.org/detain/myadmin-cpanel-licensing/version)](https://packagist.org/packages/detain/myadmin-cpanel-licensing) [![Total Downloads](https://poser.pugx.org/detain/myadmin-cpanel-licensing/downloads)](https://packagist.org/packages/detain/myadmin-cpanel-licensing) [![Latest Unstable Version](https://poser.pugx.org/detain/myadmin-cpanel-licensing/v/unstable)](//packagist.org/packages/detain/myadmin-cpanel-licensing) [![Monthly Downloads](https://poser.pugx.org/detain/myadmin-cpanel-licensing/d/monthly)](https://packagist.org/packages/detain/myadmin-cpanel-licensing) [![Daily Downloads](https://poser.pugx.org/detain/myadmin-cpanel-licensing/d/daily)](https://packagist.org/packages/detain/myadmin-cpanel-licensing) [![License](https://poser.pugx.org/detain/myadmin-cpanel-licensing/license)](https://packagist.org/packages/detain/myadmin-cpanel-licensing)


## Installation

Install with composer like

```sh
composer require detain/myadmin-cpanel-licensing
```

## License

The Cpanel Licensing Class class is licensed under the LGPL-v2.1 license.

