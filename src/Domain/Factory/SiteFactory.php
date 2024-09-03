<?php

namespace OpenCCK\Domain\Factory;

use OpenCCK\Domain\Entity\Site;
use OpenCCK\Domain\Helper\IP4Helper;
use OpenCCK\Domain\Helper\IP6Helper;
use OpenCCK\Infrastructure\API\App;
use stdClass;

class SiteFactory {
    // prettier-ignore
    const TWO_LEVEL_DOMAIN_ZONES = [
        "exnet.su","net.ru","org.ru","pp.ru","ru.net","com.ru",
        "co.bw","co.ck","co.fk","co.id","co.il","co.in","co.ke","co.ls","co.mz","co.no","co.nz","co.th","co.tz","co.uk","co.uz","co.za","co.zm","co.zw",
        "co.ae","co.at", "co.cr", "co.hu","co.jp", "co.kr", "co.ma", "co.ug", "co.ve",
        "com.az","com.bh","com.bo","com.by","com.co","com.do","com.ec","com.ee","com.es","com.gr","com.hn","com.hr","com.jo","com.lv","com.ly","com.mk","com.mx","com.my","com.pe","com.ph","com.pk","com.pt","com.ro","com.tn",
        "com.ai","com.ar","com.au","com.bd","com.bn","com.br","com.cn","com.cy","com.eg","com.et","com.fj","com.gh","com.gn","com.gt","com.gu","com.hk","com.jm","com.kh","com.kw","com.lb","com.lr","com.mt","com.mv","com.ng","com.ni","com.np","com.nr","com.om","com.pa","com.pl","com.py","com.qa","com.sa","com.sb","com.sg","com.sv","com.sy","com.tr","com.tw","com.ua","com.uy","com.ve","com.vi","com.vn","com.ye",
        "in.ua","kiev.ua","me.uk","net.cn","org.cn","org.uk","radio.am","radio.fm","eu.com"
    ];

    /**
     * @param string $name Name of portal
     * @param object $config Configuration of portal
     * @return Site
     *
     */
    static function create(string $name, object $config): Site {
        $domains = $config->domains ?? [];
        $dns = $config->dns ?? [];
        $timeout = $config->timeout ?? 1440 * 60;
        $ip4 = $config->ip4 ?? [];
        $ip6 = $config->ip6 ?? [];
        $cidr4 = $config->cidr4 ?? [];
        $cidr6 = $config->cidr6 ?? [];
        $external = $config->external ?? new stdClass();

        if (isset($external)) {
            if (isset($external->domains)) {
                foreach ($external->domains as $url) {
                    App::getLogger()->debug('Loading external domains from ' . $url);
                    $domains = array_merge($domains, explode("\n", file_get_contents($url)));
                }
            }

            if (isset($external->ip4)) {
                foreach ($external->ip4 as $url) {
                    App::getLogger()->debug('Loading external ip4 from ' . $url);
                    $ip4 = array_merge($ip4, explode("\n", file_get_contents($url)));
                }
            }

            if (isset($external->ip6)) {
                foreach ($external->ip6 as $url) {
                    App::getLogger()->debug('Loading external ip6 from ' . $url);
                    $ip6 = array_merge($ip6, explode("\n", file_get_contents($url)));
                }
            }

            if (isset($external->cidr4)) {
                foreach ($external->cidr4 as $url) {
                    App::getLogger()->debug('Loading external cidr4 from ' . $url);
                    $cidr4 = array_merge($cidr4, explode("\n", file_get_contents($url)));
                }
            }

            if (isset($external->cidr6)) {
                foreach ($external->cidr6 as $url) {
                    App::getLogger()->debug('Loading external cidr6 from ' . $url);
                    $cidr6 = array_merge($cidr6, explode("\n", file_get_contents($url)));
                }
            }
        }

        $domains = self::normalize($domains);
        $ip4 = self::normalize($ip4, true);
        $ip6 = self::normalize($ip6, true);
        $cidr4 = self::normalize(IP4Helper::processCIDR($ip4, self::normalize($cidr4)), true);
        $cidr6 = self::normalize(IP6Helper::processCIDR($ip6, self::normalize($cidr6)), true);

        return new Site($name, $domains, $dns, $timeout, $ip4, $ip6, $cidr4, $cidr6, $external);
    }

    /**
     * @param array $array
     * @param bool $excludeLocalIPs
     * @return array
     */
    public static function normalize(array $array, bool $excludeLocalIPs = false): array {
        return array_values(
            array_unique(
                array_filter(
                    $array,
                    fn(string $item) => !str_starts_with($item, '#') &&
                        strlen($item) > 0 &&
                        (!$excludeLocalIPs ||
                            (!str_starts_with($item, '10.') &&
                                !str_starts_with($item, '172.16.') &&
                                !str_starts_with($item, '192.168.') &&
                                !str_starts_with($item, 'fd')))
                )
            )
        );
    }

    /**
     * @param array $array
     * @param bool $excludeLocalIPs
     * @return array
     */
    public static function normalizeArray(array $array, bool $excludeLocalIPs = false): array {
        sort($array);
        return SiteFactory::normalize($array, $excludeLocalIPs);
    }
}
