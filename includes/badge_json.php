<?php global $post;
header('Content-Type: application/json'); 

global $wpgamify_badge_template_schema;
# Badge data
$badge_id = $post->ID;
$badge_title = get_the_title( $badge_id );
$badge_version = get_post_meta( $badge_id, 'wpgamify-badge-version', true );
$badge_desc = $wpgamify_badge_template_schema->get_post_description( $badge_id );
$badge_image_id = get_post_thumbnail_id( $badge_id );
$badge_image_url = wp_get_attachment_url( $badge_image_id );
$badge_url = get_permalink( $badge_id );

# Issuer data
$issuer_origin_parts = parse_url( get_site_url() );
$issuer_origin_url = 'http://' . $issuer_origin_parts[ 'host' ];

?>
{
  "name": "<?php echo esc_js( $badge_title ) ?>",
  "image": "<?php echo esc_js( $badge_image_url ) ?>",
  "description": "<?php echo esc_js( $badge_desc ) ?>",
  "criteria": "<?php echo esc_js( $badge_url ) ?>",
  "issuer": "<?php echo esc_js( $badge_url ) ?>issuer/"
}

<?php
