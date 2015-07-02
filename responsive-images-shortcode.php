<?php
   /*
   Plugin Name: HTML5 Responsive Image Shortcode for the WP Editor
   Plugin URI: http://www.messengerpigeons.com
   Description: Replace WordPress's default "add media" button output with a resonsive image shortcode.
   Version: 1.0
   Author: Jerry Jones
   Author URI: http://jeremyjon.es
   Twitter: @juryjowns
   License: GPL2
   */

// add all the image sizes we need.
// we're just setting the width with our numbers. We don't need to set a height. so that your vertical images
// don't get output smaller than you'd like, such as those giant infograms people seem to like
// if you want it to be contstrained, you can set it like this:
   // add_image_size('rsp_img_large', 640, 640); (max width of 640, max height of 640)
add_image_size('rsp_img_xl', 1020);
add_image_size('rsp_img_large', 640);
add_image_size('rsp_img_medium', 480);
add_image_size('rsp_img_small', 320);
add_image_size('rsp_img_tiny', 150); // not using thumbnail, since it generates a square crop


// Hijack Wordpress Add Media button output to generate our [rsp_img] shortcode
function insert_rsp_img_shortcode($html, $id, $caption, $title, $align, $url, $size) {
    // build the align attribute
    $img_align = (!empty($align) ? ' align="'.$align.'"' : '');
    // build the size attribute
    $img_size = (!empty($size) ? ' size="'.$size.'"' : '');
    // generate the shortcode with id attribute
    $rsp_img_shortcode = '[rsp_img id="'.$id.'"'.$img_align.$img_size.']';
    // send the shortcode to the editor
    return $rsp_img_shortcode;
}
add_filter( 'image_send_to_editor', 'insert_rsp_img_shortcode', 10, 9 );


// what should the shortcode do?
function rsp_img_shortcode( $atts ) {
    // get our attributes from the shortcode
    extract(shortcode_atts(array(
        'align' => '',
        'id' =>'',
        'size' => '',
    ), $atts));


    // get all of our image sizes that we set above with add_image_size() earlier
    $img_retina = wp_get_attachment_image_src( $id, 'rsp_img_xl' );
    $img_lrg = wp_get_attachment_image_src( $id, 'rsp_img_large' );
    $img_med = wp_get_attachment_image_src( $id, 'rsp_img_medium' );
    $img_small = wp_get_attachment_image_src( $id, 'rsp_img_small' );
    $img_tiny = wp_get_attachment_image_src( $id, 'rsp_img_tiny' );

    // get the alt text
    $alt_text = get_post_meta($id , '_wp_attachment_image_alt', true);

    // get the image caption
    $attachment = get_post( $id );
    $caption = $attachment->post_excerpt;

    // if they want medium or small, we'll add some media queries to serve up the right images by the viewport
    // we don't actually need to change the srcset, just the sizes attribute because we're letting the browser pick the image size
    if($size === 'thumbnail') :
        // I'm making some generalizations here to deliver the right size image
        // Thumbnail doesn't mean 150x150 here, it means 'smaller than medium but appropriately sized to the screen'
        // So, thumbnail to me means:
        // 1/4 of viewport for desktop
        // 1/3 of viewport for tablets
        // 1/2 of viewport for mobile devices
        // full width if less than that
        $img_sizes = '(min-width: 48em) 25vw
                      (min-width: 34em) 33vw,
                      (min-width: 24em) 50vw,
                      100vw';
    elseif($size === 'medium') :
        // 1/2 of viewport for desktops and tablets, full width otherwise
        $img_sizes = '(min-width: 34em) 50vw,
                      100vw';
    else :
        // if you don't specify a size, or it's something other than 'thumbnail' or 'medium',
        // we're passing you the full viewport size img, dawg
        $img_sizes = '100vw';
    endif;


    // generate the html of the image. we're adding our classes, and, if there's no caption,
    // we're adding a post-img-no-caption class for styling
    $rsp_img_html = '<img class="post-img'.(!empty($align) ? ' align-'.$align : '').(!empty($size) ? ' size-'.$size : '').(empty($caption) ? ' post-img-no-caption' : '').'"
                        sizes="'.$img_sizes.'"
                        srcset="'.$img_retina[0].' '.$img_retina[1].'w,'.
                                $img_lrg[0].' '.$img_lrg[1].'w,'.
                                $img_med[0].' '.$img_med[1].'w,'.
                                $img_small[0].' '.$img_small[1].'w,'.
                                $img_tiny[0].' '.$img_tiny[1].'w"
                        alt="'.$alt_text.'"/>';
    if(!empty($caption)) :
        // ooo! We have a caption! We should wrap that image in a figure element so we can use
        // appropriate HTML5 syntax and put the caption in a figcaption element
        $rsp_fig = '<figure class="post-figure'.(!empty($align) ? ' align-'.$align : '').(!empty($size) ? ' size-'.$size : '').'">'
                        .$rsp_img_html
                        .'<figcaption class="post-figcaption">'
                            .$caption
                        .'</figcaption>
                    </figure>';
    else :
        // no caption. let's just output the straightup img html we made earlier
        $rsp_fig = $rsp_img_html;
    endif;

    // send on the html we generated to the page
    return $rsp_fig;
}
add_shortcode('rsp_img', 'rsp_img_shortcode');


?>
