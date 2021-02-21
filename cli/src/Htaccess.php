<?php
namespace phpformsframework\cli;

use Composer\Script\Event;
use Exception;

/**
 * Class HtaccessManager
 * @package phpformsframework\cli
 */
class Htaccess
{
    public static $instance;

    private $htaccess;
    private $availables;
    private $required;
    private $htaccess_path;

    private $use_www;
    private $use_https;
    private $resource_path;

    private const SRC_PATH = "/resources/htaccess";
    private const TEMPLATE_PREFIX = "htaccess-";

    /**
     * @param string $htaccess_path
     * @throws Exception
     */
    public function __construct(string $htaccess_path)
    {
        if (file_exists($htaccess_path)) {
            $this->resource_path = dirname(dirname(__DIR__)) . self::SRC_PATH;
            $this->htaccess_path = $htaccess_path;
            $this->htaccess = file_get_contents($htaccess_path);

            $this->availables = HtaccessManager::templates($this->resource_path, self::TEMPLATE_PREFIX);
            $this->required = preg_grep('/### REQUIRE/i', file($htaccess_path));
        } else {
            throw new Exception('Invalid htaccess path', 500);
        }
    }

    public function read() : string
    {
        return $this->htaccess;
    }

    /**
     * @return self
     */
    public function enableHttpRedirect() : self
    {
        $pattern = '/(?<=### BEGIN http-redirect)(?s)(.*?)(?=### END http-redirect)/m';
        $replace = <<<RULES
\nRewriteCond %{HTTP_HOST} !^www\.
RewriteRule ^(.*)$ http://www.%{HTTP_HOST}/$1 [R=301,L]\n
RULES;
        $this->htaccess = preg_replace($pattern, $this->htaccess, $replace);
        $this->use_www = true;
        return $this;
    }

    /**
     * @return self
     */
    public function disableHttpRedirect() : self
    {
        $pattern = '/(?<=### BEGIN http-redirect)(?s)(.*?)(?=### END http-redirect)/m';
        $replace = <<<RULES
\n
RULES;
        $this->htaccess = preg_replace($pattern, $this->htaccess, $replace);
        $this->use_www = false;
        return $this;
    }

    /**
     * @return self
     */
    public function enableHttpsRedirect() : self
    {
        $pattern = '/(?<=### BEGIN https-redirect)(?s)(.*?)(?=### END https-redirect)/m';
        $prefix = $this->use_www ? "www." : "";
        $replace = <<<RULES
\nRewriteCond %{HTTPS} =off [OR]
RewriteCond %{HTTP_HOST} !^www\. [OR]
RewriteCond %{THE_REQUEST} ^[A-Z]{3,9}\ /index\.(html|php)
RewriteCond %{HTTP_HOST} ^(www\.)?(.+)$
RewriteRule ^(index\.(html|php))|(.*)$ https://{$prefix}%2/$3 [R=301,L]\n
RULES;
        $this->htaccess = preg_replace($pattern, $this->htaccess, $replace);
        $this->use_https = true;
        return $this;
    }

    /**
     * @return self
     */
    public function disableHttpsRedirect() : self
    {
        $pattern = '/(?<=### BEGIN https-redirect)(?s)(.*?)(?=### END https-redirect)/m';
        $replace = <<<RULES
\n
RULES;
        $this->htaccess = preg_replace($pattern, $this->htaccess, $replace);
        $this->use_https = true;
        return $this;
    }

    /**
     * @return self
     */
    public function addToBanlist(string $referer) : self
    {
        $pattern = '/(?<=### BEGIN security-banlist)(?s)(.*?)(?=### END security-banlist)/m';
        $base_banlist = trim($this->getMatch($pattern, $this->htaccess));

        if (false !== strpos($base_banlist, "RewriteCond %{HTTP_REFERER} {$referer} [NC")) {
            return $this;
        }

        if (strlen($base_banlist) == 0) {
            $base_banlist = file_get_contents($this->resource_path . "/htaccess-security-banlist");
            $banlist_item = "\n\tRewriteCond %{HTTP_REFERER} {$referer} [NC]\n\t";
            $subpattern = '/(?<=### BEGIN banlist-items)(?s)(.*?)(?=### END banlist-items)/m';
            $base_banlist = preg_replace($subpattern, $base_banlist, $banlist_item);
        } else {
            $subpattern = '/(?<=### BEGIN banlist-items)(?s)(.*?)(?=### END banlist-items)/m';
            $current_list = $this->getMatch($subpattern, $base_banlist);
            if (false !== $index = strrpos($current_list, "[NC]")) {
                $current_list = substr_replace($current_list, "[NC,OR]", $index, 4);
                $current_list .= "RewriteCond %{HTTP_REFERER} {$referer} [NC]\n\t";
                $subpattern = '/(?<=### BEGIN banlist-items)(?s)(.*?)(?=### END banlist-items)/m';
                $base_banlist = "\n".preg_replace($subpattern, $base_banlist, $current_list)."\n";
            }
        }
        $this->htaccess = preg_replace($pattern, $this->htaccess, $base_banlist);
        return $this;
    }

    /**
     * @return self
     */
    public function removeFromBanlist(string $referer) : self
    {
        $subpattern = '/(?<=### BEGIN banlist-items)(?s)(.*?)(?=### END banlist-items)/m';
        $current_list = $this->getMatch($subpattern, $this->htaccess);

        $item_or = "RewriteCond %{HTTP_REFERER} {$referer} [NC,OR]";
        $item_end = "RewriteCond %{HTTP_REFERER} {$referer} [NC]";
        if (false !== $index = strpos($current_list, $item_or)) {
            $current_list = substr_replace($current_list, "", $index, strlen($item_or));
        } elseif (false !== $index = strpos($current_list, $item_end)) {
            $current_list = substr_replace($current_list, "", $index, strlen($item_end));
            if (false !== $index = strrpos($current_list, "[NC,OR]")) {
                $current_list = substr_replace($current_list, "[NC]", $index, 8);
                $this->htaccess = preg_replace($subpattern, $this->htaccess, $current_list);
            }
        }
        return $this;
    }

    /**
     * @return self
     */
    public function addCorsRule($domain) : self
    {
        $pattern = '/(?<=### BEGIN security-cors)(?s)(.*?)(?=### END security-cors)/m';
        $cors_string = trim($this->getMatch($pattern, $this->htaccess));
        if (strlen($cors_string) == 0) {
            $cors_string = "\nHeader add Access-Control-Allow-Origin '".$domain."'\n";
            $this->htaccess = preg_replace($pattern, $this->htaccess, $cors_string);
        } else {
            $subpattern = '/(?<=\nHeader add Access-Control-Allow-Origin \')(?s)(.*?)(?=\'\n)/m';
            $domains = explode("|", trim($this->getMatch($subpattern, $this->htaccess)));
            if (!in_array($domain, $domains)) {
                $domains[] = $domain;
                $cors_string = "\nHeader add Access-Control-Allow-Origin '".implode("|", $domains)."'\n";
                $this->htaccess = preg_replace($pattern, $this->htaccess, $cors_string);
            }
        }
        return $this;
    }

    /**
     * @return self
     */
    public function removeCorsRule($domain) : self
    {
        $pattern = '/(?<=### BEGIN security-cors)(?s)(.*?)(?=### END security-cors)/m';
        $cors_string = trim($this->getMatch($pattern, $this->htaccess));
        if (strlen($cors_string) > 0) {
            $subpattern = '/(?<=\nHeader add Access-Control-Allow-Origin \')(?s)(.*?)(?=\'\n)/m';
            $domains = explode("|", trim($this->getMatch($subpattern, $this->htaccess)));
            if (false !== $key = array_search($domain, $domains)) {
                unset($domains[$key]);
                if (sizeof($domains) > 0) {
                    $cors_string = "\nHeader add Access-Control-Allow-Origin '" . implode("|", $domains) . "'\n";
                } else {
                    $cors_string = "\n";
                }
                $this->htaccess = preg_replace($pattern, $this->htaccess, $cors_string);
            }
        }
        return $this;
    }

    /**
     * @return self
     */
    public function addXFrameOptions($domain) : self
    {
        $pattern = '/(?<=### BEGIN x-frame-options)(?s)(.*?)(?=### END x-frame-options)/m';
        $base_xframe = trim($this->getMatch($pattern, $this->htaccess));

        if (strlen($base_xframe) == 0) {
            $base_xframe = "\n".trim(file_get_contents($this->resource_path . "/htaccess-x-frame-options"))."\n";
        }

        $subpattern = '/(?<=### BEGIN x-frame-items)(?s)(.*?)(?=### END x-frame-items)/m';
        $optionlist = trim($this->getMatch($subpattern, $base_xframe));

        $xframe_item = "\n\tHeader always set X-Frame-Options {$domain}";
        if (strlen($optionlist) == 0) {
            $optionlist .= $xframe_item;
        } else {
            if (false !== strpos($optionlist, "Header always set X-Frame-Options {$domain}")) {
                return $this;
            }
            $optionlist .= $xframe_item;
        }

        $base_xframe = preg_replace($subpattern, $base_xframe, "\n\t".$optionlist."\n\t");
        $this->htaccess = preg_replace($pattern, $this->htaccess, "\n".$base_xframe."\n");
        return $this;
    }

    /**
     * @return self
     */
    public function removeXFrameOptions($domain) : self
    {
        $pattern = '/(?<=### BEGIN x-frame-options)(?s)(.*?)(?=### END x-frame-options)/m';
        $base_xframe = trim($this->getMatch($pattern, $this->htaccess));

        $subpattern = '/(?<=### BEGIN x-frame-items)(?s)(.*?)(?=### END x-frame-items)/m';
        $optionlist = trim($this->getMatch($subpattern, $base_xframe));

        if (strlen($optionlist) == 0) {
            return $this;
        }

        $optionlist = str_replace("\tHeader always set X-Frame-Options {$domain}\n", "", $optionlist);
        return $this;
    }

    /**
     * @param string $region_name
     * @return self
     */
    public function enableRegion($region_name) : self
    {
        if (in_array($region_name, array_keys($this->availables))) {
            $this->disableRegion($region_name);
            $pattern = '/(?<=### BEGIN ' . $region_name . ')(?s)(.*?)(?=### END ' . $region_name . ')/m';
            $replace = "\n".trim(file_get_contents($this->resource_path . "/" . $region_name))."\n";
            $this->htaccess = preg_replace($pattern, $this->htaccess, $replace);
            $this->save();
        } else {
            echo "skipped: " . $region_name . " template not found!\n";
        }
        return $this;
    }

    /**
     * @param string $region_name
     * @return self
     */
    public function disableRegion($region_name) : self
    {
        if (in_array($region_name, array_keys($this->availables))) {
            $pattern = '/(?<=### BEGIN ' . $region_name . ')(?s)(.*?)(?=### END ' . $region_name . ')/m';
            $replace = "\n";
            $this->htaccess = preg_replace($pattern, $this->htaccess, $replace);
            $this->save();
        } else {
            echo "skipped: " . $region_name . " template not found!\n";
        }
        return $this;
    }

    /**
     * @param string $folder
     * @param string $match
     * @return array
     */
    public static function templates(string $folder, $match) : array
    {
        $files = glob($folder.'/'.$match.'*');
        $templates = array();
        foreach ($files as $file) {
            $comps = explode('/', $file);
            $templates[$comps[sizeof($comps)-1]] = $file;
        }
        return $templates;
    }

    public function save() : void
    {
        file_put_contents($this->htaccess_path, $this->htaccess);
    }

    // TEST METHODS
    public static function testRequire() : void
    {
        if (!file_exists('.htaccess')) {
            copy('.htaccess_sample', '.htaccess');
        } else {
            echo ".htaccess already exists\n";
        }
        $manager = new HtaccessManager('.htaccess');
    }

    public static function testEnable(Eve $event) : void
    {
        $region_name = $event->getArguments()[0];
        if (!file_exists('.htaccess')) {
            copy('.htaccess_sample', '.htaccess');
        } else {
            echo ".htaccess already exists\n";
        }
        $manager = new HtaccessManager('.htaccess');
        echo "enable " . $region_name . "\n";
        $manager->enableRegion($region_name);
    }

    public static function testDisable(Event $event) : void
    {
        $region_name = $event->getArguments()[0];
        if (!file_exists('.htaccess')) {
            copy('.htaccess_sample', '.htaccess');
        } else {
            echo ".htaccess already exists";
        }
        $manager = new HtaccessManager('.htaccess');
        echo "disable " . $region_name;
        $manager->disableRegion($region_name);
    }

    public function setup():void
    {
        echo "Resolving required regions for htaccess...\n";
        foreach ($this->required as $item) {
            $key = str_replace("### REQUIRE: ", "", $item);
            $key = str_replace("\n", "", $key);
            $this->enableRegion(trim(str_replace('### REQUIRE:', '', $item)));
        }
        echo "Completed!\n";
    }

    /**
     * @param string $pattern
     * @param string $content
     * @return string
     */
    private function getMatch(string $pattern, string $content) : string
    {
        preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE, 0);
        if (sizeof($matches) > 0) {
            $search = $matches[0][0];
            $index = $matches[0][1];
            return substr($content, $index, strlen($search));
        }
        return $content;
    }
}
