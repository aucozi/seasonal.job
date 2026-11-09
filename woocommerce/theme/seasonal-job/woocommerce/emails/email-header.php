<?php
/**
 * Email Header: seasonal job version of WooCommerce's email header.
 * Same structure and IDs as the original template; adds the shop's name block.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 10.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$store_name = $store_name ?? get_bloginfo( 'name', 'display' );
$font_url   = get_stylesheet_directory_uri() . '/assets/fonts/';
$serif      = "Albura, Georgia, 'Times New Roman', serif";
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo( 'charset' ); ?>" />
		<meta content="width=device-width, initial-scale=1.0" name="viewport">
		<title><?php echo esc_html( $store_name ); ?></title>
		<style type="text/css">
			@font-face { font-family: 'Albura'; font-style: normal; font-weight: 400; src: url('<?php echo esc_url( $font_url . 'albura_normal_400.otf' ); ?>') format('opentype'); }
			@font-face { font-family: 'Albura'; font-style: italic; font-weight: 400; src: url('<?php echo esc_url( $font_url . 'albura_italic_400.otf' ); ?>') format('opentype'); }
			@font-face { font-family: 'Albura'; font-style: normal; font-weight: 700; src: url('<?php echo esc_url( $font_url . 'albura_normal_545.otf' ); ?>') format('opentype'); }
		</style>
	</head>
	<body <?php echo is_rtl() ? 'rightmargin' : 'leftmargin'; ?>="0" marginwidth="0" topmargin="0" marginheight="0" offset="0">
		<table width="100%" id="outer_wrapper" role="presentation">
			<tr>
				<td><!-- Deliberately empty to support consistent sizing and layout across multiple email clients. --></td>
				<td width="600">
					<div id="wrapper" dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>">
						<table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" id="inner_wrapper" role="presentation">
							<tr>
								<td align="left" valign="top">
									<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation">
										<tr>
											<td class="sj-email-brand" style="padding: 40px 48px 8px; text-align: left; font-family: <?php echo esc_attr( $serif ); ?>;">
												<p style="margin: 0 0 14px; font-size: 28px; line-height: 1.2; font-weight: 400; color: #000000;"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" style="color: #000000; text-decoration: none;" target="_blank">seasonal job</a></p>
												<p style="margin: 0; font-size: 13px; line-height: 1.4; font-weight: 700; letter-spacing: 0.02em; text-transform: uppercase; color: #000000;"><a href="https://benzilinskas.com" style="color: #000000; text-decoration: none;" target="_blank">benzilinskas.com</a> <span style="font-weight: 400;">/ shop</span></p>
											</td>
										</tr>
									</table>
									<table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_container" role="presentation">
										<tr>
											<td align="center" valign="top">
												<!-- Header -->
												<table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_header" role="presentation">
													<tr>
														<td id="header_wrapper">
															<h1><?php echo esc_html( $email_heading ); ?></h1>
														</td>
													</tr>
												</table>
												<!-- End Header -->
											</td>
										</tr>
										<tr>
											<td align="center" valign="top">
												<!-- Body -->
												<table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_body" role="presentation">
													<tr>
														<td valign="top" id="body_content">
															<!-- Content -->
															<table border="0" cellpadding="20" cellspacing="0" width="100%" role="presentation">
																<tr>
																	<td valign="top" id="body_content_inner_cell">
																		<div id="body_content_inner">
