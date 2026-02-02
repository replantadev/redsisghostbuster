<?php
/**
 * Sistema de actualizaciones automáticas desde GitHub
 * Similar al sistema usado por DNIwoo
 * 
 * Detecta nuevas versiones en releases/tags del repositorio
 */

if (!defined('ABSPATH')) {
    exit;
}

class Replanta_Ghost_Orders_GitHub_Updater {
    
    /**
     * Configuración del repositorio
     */
    private $github_username = 'replantadev';
    private $github_repo = 'redsisghostbuster';
    private $plugin_slug;
    private $plugin_file;
    private $current_version;
    private $github_response = null;
    private $cache_key = 'replanta_god_github_update';
    private $cache_expiration = 12 * HOUR_IN_SECONDS; // 12 horas
    
    /**
     * Constructor
     */
    public function __construct($plugin_file) {
        $this->plugin_file = $plugin_file;
        $this->plugin_slug = plugin_basename($plugin_file);
        
        // Obtener versión actual del plugin
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $plugin_data = get_plugin_data($plugin_file);
        $this->current_version = $plugin_data['Version'];
    }
    
    /**
     * Inicializar hooks
     */
    public static function init() {
        $instance = new self(REPLANTA_GOD_FILE);
        
        // Hook para verificar actualizaciones
        add_filter('pre_set_site_transient_update_plugins', [$instance, 'check_update']);
        
        // Hook para mostrar información del plugin
        add_filter('plugins_api', [$instance, 'plugin_info'], 20, 3);
        
        // Hook después de instalar actualización
        add_filter('upgrader_post_install', [$instance, 'after_install'], 10, 3);
        
        // Limpiar caché cuando se fuerza verificación
        add_action('wp_update_plugins', [$instance, 'clear_cache']);
        
        // Añadir enlace "Ver detalles" en plugins
        add_filter('plugin_row_meta', [$instance, 'plugin_row_meta'], 10, 2);
    }
    
    /**
     * Obtener información del último release de GitHub
     */
    private function get_github_release_info() {
        // Verificar caché primero
        $cached = get_transient($this->cache_key);
        if ($cached !== false) {
            return $cached;
        }
        
        // URL de la API de GitHub para releases
        $url = sprintf(
            'https://api.github.com/repos/%s/%s/releases/latest',
            $this->github_username,
            $this->github_repo
        );
        
        $response = wp_remote_get($url, [
            'headers' => [
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url'),
            ],
            'timeout' => 10,
        ]);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            // Si no hay releases, intentar con tags
            return $this->get_github_tag_info();
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (empty($body) || !isset($body['tag_name'])) {
            return false;
        }
        
        $release_info = [
            'version' => ltrim($body['tag_name'], 'v'),
            'download_url' => $this->get_download_url($body),
            'changelog' => $body['body'] ?? '',
            'published_at' => $body['published_at'] ?? '',
            'html_url' => $body['html_url'] ?? '',
            'name' => $body['name'] ?? $body['tag_name'],
        ];
        
        // Guardar en caché
        set_transient($this->cache_key, $release_info, $this->cache_expiration);
        
        return $release_info;
    }
    
    /**
     * Obtener información del último tag (fallback si no hay releases)
     */
    private function get_github_tag_info() {
        $url = sprintf(
            'https://api.github.com/repos/%s/%s/tags',
            $this->github_username,
            $this->github_repo
        );
        
        $response = wp_remote_get($url, [
            'headers' => [
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url'),
            ],
            'timeout' => 10,
        ]);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (empty($body) || !isset($body[0]['name'])) {
            return false;
        }
        
        // El primer tag es el más reciente
        $latest_tag = $body[0];
        
        $release_info = [
            'version' => ltrim($latest_tag['name'], 'v'),
            'download_url' => sprintf(
                'https://github.com/%s/%s/archive/refs/tags/%s.zip',
                $this->github_username,
                $this->github_repo,
                $latest_tag['name']
            ),
            'changelog' => '',
            'published_at' => '',
            'html_url' => sprintf(
                'https://github.com/%s/%s/releases/tag/%s',
                $this->github_username,
                $this->github_repo,
                $latest_tag['name']
            ),
            'name' => $latest_tag['name'],
        ];
        
        // Guardar en caché
        set_transient($this->cache_key, $release_info, $this->cache_expiration);
        
        return $release_info;
    }
    
    /**
     * Obtener URL de descarga del release
     */
    private function get_download_url($release) {
        // Primero buscar un asset .zip
        if (!empty($release['assets'])) {
            foreach ($release['assets'] as $asset) {
                if (strpos($asset['name'], '.zip') !== false) {
                    return $asset['browser_download_url'];
                }
            }
        }
        
        // Si no hay asset, usar el zipball
        return $release['zipball_url'] ?? sprintf(
            'https://github.com/%s/%s/archive/refs/tags/%s.zip',
            $this->github_username,
            $this->github_repo,
            $release['tag_name']
        );
    }
    
    /**
     * Verificar si hay actualizaciones disponibles
     */
    public function check_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }
        
        $release_info = $this->get_github_release_info();
        
        if (!$release_info) {
            return $transient;
        }
        
        // Comparar versiones
        if (version_compare($release_info['version'], $this->current_version, '>')) {
            $plugin_data = [
                'slug' => dirname($this->plugin_slug),
                'plugin' => $this->plugin_slug,
                'new_version' => $release_info['version'],
                'url' => sprintf(
                    'https://github.com/%s/%s',
                    $this->github_username,
                    $this->github_repo
                ),
                'package' => $release_info['download_url'],
                'icons' => [
                    '1x' => REPLANTA_GOD_URL . 'assets/images/icon-128x128.png',
                    '2x' => REPLANTA_GOD_URL . 'assets/images/icon-256x256.png',
                ],
                'banners' => [
                    'low' => REPLANTA_GOD_URL . 'assets/images/banner-772x250.png',
                    'high' => REPLANTA_GOD_URL . 'assets/images/banner-1544x500.png',
                ],
                'tested' => '6.4',
                'requires_php' => '7.4',
                'compatibility' => new stdClass(),
            ];
            
            $transient->response[$this->plugin_slug] = (object) $plugin_data;
        }
        
        return $transient;
    }
    
    /**
     * Mostrar información del plugin en el modal de detalles
     */
    public function plugin_info($result, $action, $args) {
        if ($action !== 'plugin_information') {
            return $result;
        }
        
        if (!isset($args->slug) || $args->slug !== dirname($this->plugin_slug)) {
            return $result;
        }
        
        $release_info = $this->get_github_release_info();
        
        if (!$release_info) {
            return $result;
        }
        
        $plugin_data = get_plugin_data(REPLANTA_GOD_FILE);
        
        $info = new stdClass();
        $info->name = $plugin_data['Name'];
        $info->slug = dirname($this->plugin_slug);
        $info->version = $release_info['version'];
        $info->author = sprintf('<a href="%s">%s</a>', $plugin_data['AuthorURI'], $plugin_data['Author']);
        $info->homepage = $plugin_data['PluginURI'];
        $info->requires = '5.0';
        $info->tested = '6.4';
        $info->requires_php = '7.4';
        $info->downloaded = 0;
        $info->last_updated = $release_info['published_at'];
        $info->download_link = $release_info['download_url'];
        
        // Secciones
        $info->sections = [
            'description' => $plugin_data['Description'],
            'changelog' => $this->format_changelog($release_info['changelog']),
            'installation' => $this->get_installation_instructions(),
        ];
        
        // Banners e iconos
        $info->banners = [
            'low' => REPLANTA_GOD_URL . 'assets/images/banner-772x250.png',
            'high' => REPLANTA_GOD_URL . 'assets/images/banner-1544x500.png',
        ];
        
        return $info;
    }
    
    /**
     * Formatear changelog de Markdown a HTML
     */
    private function format_changelog($markdown) {
        if (empty($markdown)) {
            return '<p>Ver el <a href="https://github.com/' . $this->github_username . '/' . $this->github_repo . '/releases" target="_blank">historial de releases en GitHub</a>.</p>';
        }
        
        // Conversión básica de Markdown
        $html = $markdown;
        $html = preg_replace('/^### (.*)$/m', '<h4>$1</h4>', $html);
        $html = preg_replace('/^## (.*)$/m', '<h3>$1</h3>', $html);
        $html = preg_replace('/^# (.*)$/m', '<h2>$1</h2>', $html);
        $html = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $html);
        $html = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $html);
        $html = preg_replace('/`(.*?)`/', '<code>$1</code>', $html);
        $html = preg_replace('/^- (.*)$/m', '<li>$1</li>', $html);
        $html = preg_replace('/(<li>.*<\/li>)/s', '<ul>$1</ul>', $html);
        $html = nl2br($html);
        
        return $html;
    }
    
    /**
     * Instrucciones de instalación
     */
    private function get_installation_instructions() {
        return '
        <ol>
            <li>Descarga el plugin desde GitHub</li>
            <li>Ve a <strong>Plugins → Añadir nuevo → Subir plugin</strong></li>
            <li>Selecciona el archivo ZIP descargado</li>
            <li>Haz clic en <strong>Instalar ahora</strong></li>
            <li>Activa el plugin</li>
            <li>Configura en <strong>WooCommerce → 👻 Pedidos Fantasma</strong></li>
        </ol>
        <p>Para más información, visita el <a href="https://github.com/' . $this->github_username . '/' . $this->github_repo . '" target="_blank">repositorio en GitHub</a>.</p>
        ';
    }
    
    /**
     * Después de instalar la actualización
     */
    public function after_install($response, $hook_extra, $result) {
        global $wp_filesystem;
        
        // Verificar que es nuestro plugin
        if (!isset($hook_extra['plugin']) || $hook_extra['plugin'] !== $this->plugin_slug) {
            return $result;
        }
        
        // El directorio descargado de GitHub tiene un sufijo con el tag/branch
        // Necesitamos renombrarlo al nombre correcto del plugin
        $plugin_folder = WP_PLUGIN_DIR . '/' . dirname($this->plugin_slug);
        
        // Mover al directorio correcto
        $wp_filesystem->move($result['destination'], $plugin_folder);
        $result['destination'] = $plugin_folder;
        
        // Reactivar el plugin si estaba activo
        if (is_plugin_active($this->plugin_slug)) {
            activate_plugin($this->plugin_slug);
        }
        
        return $result;
    }
    
    /**
     * Limpiar caché de actualizaciones
     */
    public function clear_cache() {
        delete_transient($this->cache_key);
    }
    
    /**
     * Forzar verificación de actualizaciones
     */
    public static function force_check() {
        delete_transient('replanta_god_github_update');
        delete_site_transient('update_plugins');
        wp_update_plugins();
    }
    
    /**
     * Añadir enlaces en la fila del plugin
     */
    public function plugin_row_meta($links, $file) {
        if ($file !== $this->plugin_slug) {
            return $links;
        }
        
        $links[] = sprintf(
            '<a href="%s" target="_blank">%s</a>',
            'https://github.com/' . $this->github_username . '/' . $this->github_repo,
            '⭐ GitHub'
        );
        
        $links[] = sprintf(
            '<a href="%s" target="_blank">%s</a>',
            'https://github.com/' . $this->github_username . '/' . $this->github_repo . '/issues',
            '🐛 Reportar bug'
        );
        
        return $links;
    }
}
