<?php
if ( ! defined( 'ABSPATH' ) ) exit;

use Carbon_Fields\Container;
use Carbon_Fields\Field;
use TwwGlossary\Admin\TwwgAdminMenu;
?>

<div class="protein-calculator-container wrap" id="tww-admin">
    <div class="twwc--page-title-wrapper">
        <div class="twwc--page-header">
        <?php
            $logo = TWWG_GLOSSARY_PLUGIN_URL . 'assets/images/twwlogo70.webp';
            echo '<h1>TWWG Settings</h1>';
        ?>
        </div>
    </div>

    <div class="bluefield_content_wrapper">
        <?php
            $settings_slug = TwwgAdminMenu::TWWG_COMMON_SETTINGS_PAGE;
            $active_tab = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : $settings_slug;
        ?>

        <h2 class="nav-tab-wrapper">
            <a href="?page=<?php echo $settings_slug; ?>" class="nav-tab <?php echo ($active_tab == $settings_slug) ? 'nav-tab-active' : ''; ?>">Settings</a>
        </h2>

        <form method="post" action="options.php">
            <?php
                if ($active_tab === $settings_slug) {
                    submit_button();
                }
            ?>
        </form>
        
        <div id="carbon-fields-container">
        </div>
    </div>
</div>

