<?php
/**
 * Email Footer: seasonal job version of WooCommerce's email footer.
 * Closes the header's tables and adds the shop's links and copyright line.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 10.4.0
 */

defined( 'ABSPATH' ) || exit;

$email  = $email ?? null;
$serif  = "Albura, Georgia, 'Times New Roman', serif";
$link   = 'color: #000000; text-decoration: underline;';
$footer = get_option( 'woocommerce_email_footer_text' );
/** This filter is documented in templates/emails/email-styles.php */
if ( apply_filters( 'woocommerce_is_email_preview', false ) ) {
	$text_transient = get_transient( 'woocommerce_email_footer_text' );
	$footer         = false !== $text_transient ? $text_transient : $footer;
}
/** This filter is documented in WooCommerce's templates/emails/email-footer.php */
$footer = trim( (string) apply_filters( 'woocommerce_email_footer_text', $footer, $email ) );
?>
																		</div>
																	</td>
																</tr>
															</table>
															<!-- End Content -->
														</td>
													</tr>
												</table>
												<!-- End Body -->
											</td>
										</tr>
									</table>
								</td>
							</tr>
							<tr>
								<td align="left" valign="top">
									<!-- Footer -->
									<table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_footer" role="presentation">
										<tr>
											<td valign="top" id="credit" style="padding: 24px 48px 40px; text-align: left; font-family: <?php echo esc_attr( $serif ); ?>; color: #000000;">
												<p style="margin: 0 0 10px; padding-top: 18px; border-top: 1px solid #000000; font-size: 15px; line-height: 1.6;">
													<a href="https://instagram.com/seasonal.job" style="<?php echo esc_attr( $link ); ?>" target="_blank">Instagram @seasonal.job</a> &nbsp;·&nbsp;
													<a href="https://open.spotify.com/artist/2Cdr4xt4kY61ECVbILaAFp" style="<?php echo esc_attr( $link ); ?>" target="_blank">Spotify</a> &nbsp;·&nbsp;
													<a href="<?php echo esc_url( home_url( '/' ) ); ?>" style="<?php echo esc_attr( $link ); ?>" target="_blank">shop.benzilinskas.com</a>
												</p>
												<?php if ( $footer !== '' && $footer !== '{site_title}' ) : ?>
													<div style="margin: 0 0 10px; font-size: 13px; color: #6d6466;"><?php echo wp_kses_post( wpautop( wptexturize( $footer ) ) ); ?></div>
												<?php endif; ?>
												<p style="margin: 0; font-size: 11px; color: #000000;">Ben Zilinskas / Seasonal Job All rights reserved © <?php echo esc_html( gmdate( 'Y' ) ); ?></p>
											</td>
										</tr>
									</table>
									<!-- End Footer -->
								</td>
							</tr>
						</table>
					</div>
				</td>
				<td><!-- Deliberately empty to support consistent sizing and layout across multiple email clients. --></td>
			</tr>
		</table>
	</body>
</html>
