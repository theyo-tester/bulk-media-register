/**
 * Bulk Media Register
 *
 * @package    Bulk Media Register
 * @subpackage jquery.bulkmediaregister.js
/*
	Copyright (c) 2020- Katsushi Kawamori (email : dodesyoswift312@gmail.com)
	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; version 2 of the License.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

jQuery(
	function ($) {

		/* Control of the Enter key */
		$( 'input[type!="submit"][type!="button"]' ).keypress(
			function (e) {
				if ((e.which && e.which == 13) || (e.keyCode && e.keyCode == 13)) {
					return false;
				} else {
					return true;
				}
			}
		);

		/* Ajax for Register */
		var bulkmediaregister_defer = $.Deferred().resolve();
		$( '#bulkmediaregister_ajax_update' ).click(
			function () {

				$( "#bulkmediaregister-loading-container" ).empty();
				$( "#bulkmediaregister-register-container" ).empty();

				$( "#bulkmediaregister-loading-container" ).append( "<div id=\"bulkmediaregister-update-progress\"><progress value=\"0\" max=\"100\"></progress> 0%</div><button type=\"button\" id=\"bulkmediaregister_ajax_stop\">" + bulkmediaregister_text.stop_button + "</button>" );
				$( "#bulkmediaregister-loading-container" ).append( "<div id=\"bulkmediaregister-update-result\"></div>" );
				var update_continue = true;
				/* Stop button */
				$( "#bulkmediaregister_ajax_stop" ).click(
					function () {
						update_continue = false;
						$( "#bulkmediaregister_ajax_stop" ).text( bulkmediaregister_text.stop_message );
					}
				);

				var count = 0;
				var success_count = 0;
				var error_count = 0;
				var error_update = "";
				var files = JSON.parse( bulkmediaregister_data.file );

				$.each(
					files,
					function (i) {
						var j = i;
						bulkmediaregister_defer = bulkmediaregister_defer.then(
							function () {
								if ( update_continue == true ) {
									return $.ajax(
										{
											type: 'POST',
											cache : false,
											url: bulkmediaregister.ajax_url,
											data: {
												'action': bulkmediaregister.action,
												'nonce': bulkmediaregister.nonce,
												'maxcount': bulkmediaregister_data.count,
												'uid': bulkmediaregister_data.uid,
												'file': files[j],
											}
										}
									).then(
										function (result) {
											count += 1;
											success_count += 1;
											$( "#bulkmediaregister-update-result" ).append( result );
											$( "#bulkmediaregister-update-progress" ).empty();
											var progressper = Math.round( ( count / bulkmediaregister_data.count ) * 100 );
											$( "#bulkmediaregister-update-progress" ).append( "<progress value=\"" + progressper + "\" max=\"100\"></progress> " + progressper + "%" );
											if ( count == bulkmediaregister_data.count || update_continue == false ) {
												$.ajax(
													{
														type: 'POST',
														url: bulkmediaregister_mes.ajax_url,
														data: {
															'action': bulkmediaregister_mes.action,
															'nonce': bulkmediaregister_mes.nonce,
															'error_count': error_count,
															'error_update': error_update,
															'success_count': success_count,
															'uid': bulkmediaregister_data.uid,
														}
													}
												).done(
													function (result) {
														$( "#bulkmediaregister-update-progress" ).empty();
														$( "#bulkmediaregister-update-progress" ).append( result );
														$( "#bulkmediaregister_ajax_stop" ).hide();
													}
												);
											}
										}
									).fail(
										function ( jqXHR, textStatus, errorThrown) {
											error_count += 1;
											error_update += "<div>" + files[j] + ": error -> status " + jqXHR.status + ' ' + textStatus.status + "</div>";
										}
									);
								}
							}
						);
					}
				);
				return false;
			}
		);

	}
);
