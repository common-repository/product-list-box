<?php

/*
Plugin Name: Product List Box
Description: Provides a Shortcode [product_listing]. Place it on any Post / Page / Custom Post Type to display three random Products (Price, Name, Description), based on Material Design Bootstrap Stylesheet
Version: 1.0
Author: Hendrik Bäker
Author URI: https://baeker-it.de
License: GPL2
*/

function plb_baeker_listRandomProducts($atts)
{
    $options = get_option('shortcode_productlisting');
    $category = shortcode_atts(array(
        'id' => false,
    ), $atts);
    if (!$category['id']) {
        $list_args = array(
            'posts_per_page' => 3,
            'product_cat' => $options['category'],
            'orderby' => 'rand',
            'post_type' => 'product'
        );
    } else {
        $list_args = [
            'posts_per_page' => 3,
            'product_cat' => $category['id'],
            'orderby' => 'rand',
            'post_type' => 'product'
        ];
    }
    if (count(get_posts($list_args)) == 0) {
        $list_args['product_cat'] = '';
    }
    $randomProducts = get_posts($list_args);
    $return = "<div class='row'>";
    foreach ($randomProducts As $product) {
        setup_postdata($product);
        $permalink = get_the_permalink($product->ID);
        $image = get_the_post_thumbnail($product->ID, 'medium');
        $title = get_the_title($product->ID);
        $excerpt = strip_tags(get_the_content());
        $excerpt = preg_replace('/' . $title . '/', "", $excerpt, 1);
        $excerpt = substr($excerpt, 0, 150);
        if (strlen($title) > 17) {
            $title = substr($title, 0, 27) . "..";
        }
        $excerpt .= "....";
        $price = get_post_meta($product->ID, '_price', true);
        $return .= '<div class="col-lg-4 col-md-6">
                        <div class="card pricing-card">
                        <!--Price-->
                        <div class="price header red">
                        <p style="font-size:1rem;margin-top: .7rem;color:white !important;"><span style="line-height:4.5rem;font-size:3rem;color:white;">' . $price . ' €</span><br/> inkl. MwSt. zzgl. <a href="' . $options['deliverynotes'] . '" target="_blank" style="color:white"><span style="font-weight: bold;">Versandkosten</span></a></p>
                            <div class="version">
                                <h5 style="color:white;margin-top:1rem">' . $title . '</h5>
                            </div>
                        </div>
                        <!--/.Price-->

                        <!--Features-->
                        <div class="card-block striped">
                        ' . $image . '
                            <p style="word-wrap: break-all;">' . $excerpt . '</p>
                            <a href="' . $permalink . '" class="btn btn-primary waves-effect waves-light">zum Artikel</a>
                        </div>
                        <!--/.Features-->

                    </div>
                    </div>';
    }
    $return .= "</div><div class='clearfix'></div>";
    return $return;
    wp_reset_postdata();
}

add_action('init', 'plb_baeker_loadStylesheet');
function plb_baeker_loadStylesheet()
{
    wp_enqueue_style('product-list-box-css', plugins_url('assets/product-list-box.css', __FILE__));
}

add_shortcode('product_listing', 'plb_baeker_listRandomProducts');

function plb_baeker_listRandomProductsSettings()
{

    ?>
    <div class="wrap">
        <h2>Einstellungen für die Produktebox</h2>
        <p>Nutzen Sie den Shortcode [product_listing] ohne Attribute, um die hier ausgewählen Kategorien für die
            zufällige Auswahl zu nutzen.</p>
        <p>Wenn Sie eine spezifische Kategorie nutzen möchten, nutzen Sie als Attribut id den Slug der jeweiligen
            Kategorie.</p>
        <form method="post" action="options.php" enctype="multipart/form-data">
            <?php
            settings_fields('shortcode_productlistings');
            $product_box_settings = get_option('shortcode_productlisting');
            ?><h3><label>Kategorieauswahl</label></h3>
            <select name='shortcode_productlisting[category][]' multiple style="width:100%; height:400px">
                <?php
                $product_cats = get_terms('product_cat', ['hide_empty' => false]);

                foreach ($product_cats as $cat) {
                    $catname = str_replace(" ", "-", strtolower($cat->name));
                    if ($product_box_settings['category'] == $cat->term_id) {
                        echo "<option value='$catname' selected>$cat->name</option>";
                    } else {
                        echo "<option value='$catname'>$cat->name</option>";
                    }
                }

                ?>
            </select>
            <p>
                <label>Link zu den Versandhinweisen: </label><select name="shortcode_productlisting[deliverynotes]">
                    <?php
                    foreach (get_posts(['post_type' => 'page', 'numberposts' => -1, 'post_status' => 'publish']) as $page) {
                        if (!empty($product_box_settings['deliverynotes']) and $product_box_settings['deliverynotes'] == get_permalink($page->ID)) {
                            echo "<option value='" . get_permalink($page->ID) . "' selected>$page->post_title</option>";
                        } else {
                            echo "<option value='" . get_permalink($page->ID) . "'>$page->post_title</option>";
                        }
                    }
                    ?>
                </select>
            </p>
            <p>
                <input type="submit" name="submit" class="button-primary" value="Einstellungen speichern"/>
            </p>
        </form>
        <h4>Aktuelle Kategorien:</h4>
        <ul>
            <?php
            foreach (explode(",", $product_box_settings['category']) as $cat) {
                echo "<li>" . ucfirst($cat) . "</li>";
            }
            ?>
        </ul>
    </div>
    <?php
}

add_action('admin_init', 'plb_baeker_shortcode_productbox_init');
function plb_baeker_shortcode_productbox_init()
{
    register_setting('shortcode_productlistings', 'shortcode_productlisting', 'plb_baekershortcode_productlisting_validate');
}

function plb_baeker_shortcode_productlisting_validate($input)
{
    $tmp = "";
    foreach ($input['category'] as $value) {
        $tmp .= $value . ",";
    }
    $input['category'] = $tmp;

    return $input;
}

add_action('admin_menu', 'plb_baeker_productBoxAdmin');

function plb_baeker_productBoxAdmin()
{
    add_menu_page('Shortcode Produktebox', 'Shortcode Produktebox', 'edit_posts', 'shortcode_product_listing', 'listRandomProductsSettings', NULL, 4);
}