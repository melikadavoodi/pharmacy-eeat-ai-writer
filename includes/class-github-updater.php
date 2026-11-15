<?php
if (!defined('ABSPATH')) {
    exit;
}

class Pharmacy_EEAT_GitHub_Updater {

    private $slug;
    private $plugin_data;
    private $username;
    private $repo;
    private $plugin_file;
    private $github_api_result;
    private $access_token;

    public function __construct($plugin_file) {
        $this->plugin_file = $plugin_file;
        $this->slug        = plugin_basename($plugin_file);

        // Fetch repo info from settings or default
        $full_repo = get_option('pharmacy_ai_github_repo', 'melikadavoodi/pharmacy-eeat-ai-writer');
        $parts = explode('/', trim($full_repo));
        $this->username = $parts[0] ?? 'melikadavoodi';
        $this->repo     = $parts[1] ?? 'pharmacy-eeat-ai-writer';
        $this->access_token = get_option('pharmacy_ai_github_token', '');

        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_update'));
        add_filter('plugins_api', array($this, 'plugin_popup'), 20, 3);
        add_filter('upgrader_post_install', array($this, 'after_install'), 10, 3);
    }

    private function get_repository_info() {
        if (!empty($this->github_api_result)) {
            return $this->github_api_result;
        }

        $url = "https://api.github.com/repos/{$this->username}/{$this->repo}/releases/latest";

        $args = array(
            'timeout' => 15,
            'headers' => array(
                'Accept'     => 'application/vnd.github.v3+json',
                'User-Agent' => 'WordPress-Pharmacy-AI-Updater',
            ),
        );

        if (!empty($this->access_token)) {
            $args['headers']['Authorization'] = "token {$this->access_token}";
        }

        $response = wp_remote_get($url, $args);

        if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
            return false;
        }

        $response_body = wp_remote_retrieve_body($response);
        $result = json_decode($response_body);

        $this->github_api_result = $result;
        return $result;
    }

    public function check_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $this->plugin_data = get_plugin_data($this->plugin_file);
        $current_version   = $this->plugin_data['Version'];

        $release = $this->get_repository_info();
        if (!$release || !isset($release->tag_name)) {
            return $transient;
        }

        $new_version = ltrim($release->tag_name, 'v');

        if (version_compare($current_version, $new_version, '<')) {
            $package = $release->zipball_url;

            // If an attached release asset exists, prefer it
            if (!empty($release->assets) && is_array($release->assets)) {
                foreach ($release->assets as $asset) {
                    if (substr($asset->name, -4) === '.zip') {
                        $package = $asset->browser_download_url;
                        break;
                    }
                }
            }

            $obj = new stdClass();
            $obj->slug        = current(explode('/', $this->slug));
            $obj->new_version = $new_version;
            $obj->url         = $release->html_url;
            $obj->package     = $package;
            $obj->tested      = '6.7';
            $obj->requires_php = '7.4';

            $transient->response[$this->slug] = $obj;
        }

        return $transient;
    }

    public function plugin_popup($result, $action, $args) {
        if ('plugin_information' !== $action || !isset($args->slug) || $args->slug !== current(explode('/', $this->slug))) {
            return $result;
        }

        $release = $this->get_repository_info();
        if (!$release) {
            return $result;
        }

        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $this->plugin_data = get_plugin_data($this->plugin_file);

        $res = new stdClass();
        $res->name           = $this->plugin_data['Name'];
        $res->slug           = $args->slug;
        $res->version        = ltrim($release->tag_name, 'v');
        $res->author         = $this->plugin_data['AuthorName'];
        $res->homepage       = $this->plugin_data['PluginURI'];
        $res->requires       = '5.8';
        $res->tested         = '6.7';
        $res->requires_php   = '7.4';
        $res->last_updated   = $release->published_at;
        $res->sections       = array(
            'description' => $this->plugin_data['Description'],
            'changelog'   => nl2br(esc_html($release->body ?? 'به‌روزرسانی و رفع ایرادات.')),
        );

        return $res;
    }

    public function after_install($response, $hook_extra, $result) {
        global $wp_filesystem;

        $proper_folder_name = current(explode('/', $this->slug));
        $install_directory  = plugin_dir_path($this->plugin_file);

        // Rename folder if GitHub named it repo-tag
        if ($result['destination_name'] !== $proper_folder_name) {
            $proper_destination = trailingslashit($result['local_destination']) . $proper_folder_name;
            $wp_filesystem->move($result['destination'], $proper_destination);
            $result['destination'] = $proper_destination;
            $result['destination_name'] = $proper_folder_name;
        }

        activate_plugin($this->slug);
        return $result;
    }
}
