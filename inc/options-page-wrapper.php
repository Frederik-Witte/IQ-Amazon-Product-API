<div class="wrap">

	<div id="icon-options-general" class="icon32"></div>
	<h2>IQ Amazon Product API Plugin</h2>

	<div id="poststuff">

		<div id="post-body" class="metabox-holder columns-3">

			<!-- main content -->
			<div id="post-body-content">

				<div class="meta-box-sortables ui-sortable">

					<?php

					if( isset( $iq_amazon_api_key ) ) {

					} else {
						$iq_amazon_api_key = '';
					}

					if( isset( $iq_amazon_secret_key ) ) {

					} else {
						$iq_amazon_secret_key = '';
					}

					if( isset( $iq_amazon_api_key_2 ) ) {

					} else {
						$iq_amazon_api_key_2 = '';
					}

					if( isset( $iq_amazon_secret_key_2 ) ) {

					} else {
						$iq_amazon_secret_key_2 = '';
					}

					if( isset( $iq_amazon_associate_tag ) ) {

					} else {
						$iq_amazon_associate_tag = '';
					}

					if( isset( $iq_ebay_campaign_id) ) {

					} else {
						$iq_ebay_campaign_id = '';
					}


					?>

					<div class="postbox">

						<h3><span>Let's Get Started!</span></h3>
						<div class="inside">

							<form name="iq_amazon_api_data_form" method="post" action="">

							<input type="hidden" name="iq_amazon_form_submitted" value="Y" />

							<table class="form-table">
								<tr>
									<td>
										<label for="iq_amazon_api_key">API Key</label>
									</td>
									<td>
										<input name="iq_amazon_api_key" value="<?php echo $iq_amazon_api_key ?>" id="iq_amazon_api_key" type="text" value="" class="regular-text" />
									</td>
								</tr>
								<tr>
									<td>
										<label for="iq_amazon_secret_key">Secret Key</label>
									</td>
									<td>
										<input name="iq_amazon_secret_key" value="<?php echo $iq_amazon_secret_key ?>" id="wptreehouse_secret_key" type="text" value="" class="regular-text" />
									</td>
								</tr>
								<tr>
									<td>
										<label for="iq_amazon_api_key_2">API Key 2</label>
									</td>
									<td>
										<input name="iq_amazon_api_key_2" value="<?php echo $iq_amazon_api_key_2 ?>" id="iq_amazon_api_key_2" type="text" value="" class="regular-text" />
									</td>
								</tr>
								<tr>
									<td>
										<label for="iq_amazon_secret_key_2">Secret Key_2</label>
									</td>
									<td>
										<input name="iq_amazon_secret_key_2" value="<?php echo $iq_amazon_secret_key_2 ?>" id="wptreehouse_secret_key_2" type="text" value="" class="regular-text" />
									</td>
								</tr>
								<tr>
									<td>
										<label for="iq_amazon_associate_tag">Associate Tag</label>
									</td>
									<td>
										<input name="iq_amazon_associate_tag" value="<?php echo $iq_amazon_associate_tag ?>" id="iq_amazon_associate_tag" type="text" value="" class="regular-text" />
									</td>
								</tr>
								<tr>
									<td>
										<label for="iq_ebay_campaign_id">Ebay Campaign ID</label>
									</td>
									<td>
										<input name="iq_ebay_campaign_id" value="<?php echo $iq_ebay_campaign_id ?>" id="iq_ebay_campaign_id" type="text" value="" class="regular-text" />
									</td>
								</tr>
							</table>

							<p>
								<input class="button-primary" type="submit" name="iq_amazon_api_data_submit" value="Save" />
							</p>

							</form>


						</div> <!-- .inside -->

					</div> <!-- .postbox -->
				</div> <!-- .meta-box-sortables .ui-sortable -->

			</div> <!-- post-body-content -->

		</div> <!-- #post-body .metabox-holder .columns-2 -->

		<br class="clear">
	</div> <!-- #poststuff -->

</div> <!-- .wrap -->
