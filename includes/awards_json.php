<?php global $post;
header('Content-Type: application/json'); 

# Award data
$email = get_post_meta( $post->ID, 'wpgamify-award-email-address', true );
$salt = get_post_meta( $post->ID, 'wpgamify-award-salt', true );
$issued_on = get_the_date('c', $post->ID);
$evidence = get_permalink();
$uid = $post->post_name;

# Badge data
$badge_id = get_post_meta( $post->ID, 'wpgamify-award-choose-badge', true );
$badge_title = get_the_title( $badge_id );
$badge_version = get_post_meta( $badge_id, 'wpgamify-badge-version', true );
$badge_desc = $wpgamify_badge_template_schema->get_post_description( $badge_id );
$badge_image_id = get_post_thumbnail_id( $badge_id );
$badge_image_url = wp_get_attachment_url( $badge_image_id );
$badge_url = get_permalink( $badge_id );

# Issuer data
$issuer_origin_parts = parse_url( get_site_url() );
$issuer_origin_url = 'http://' . $issuer_origin_parts[ 'host' ];
$issuer_name = get_option( 'wpgamify_issuer_name' );
$issuer_org = get_option( 'wpgamify_issuer_org' );
$issuer_contact = get_option( 'wpgamify_issuer_contact' );
if (empty( $issuer_contact ))
    $issuer_contact = get_bloginfo( 'admin_email' );

?>
{
  "recipient": {
    "type": "email",
    "hashed": true,
    "salt": "<?php echo esc_js( $salt ) ?>",
    "identity": "sha256$<?php echo hash( "sha256", ($email . $salt) ) ?>"
  },
  "evidence": "<?php echo esc_js( $evidence ) ?>",
  "issuedOn": "<?php echo esc_js( $issued_on ) ?>",
  "uid": "<?php echo esc_js( $uid ) ?>",
  "badge": "<?php echo esc_js( $badge_url ) ?>json/",
  "verify": {
    "type": "hosted",
    "url": "<?php echo esc_js( $evidence ) ?>json/"
  }
}

<?php
