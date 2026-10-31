/**
 * Bulk Media Register
 *
 * @package    Bulk Media Register
 * @subpackage jquery.selectmediaregister.js
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
		var selectmediaregister_defer = $.Deferred().resolve();
		$( '#selectmediaregister_ajax_update1, #selectmediaregister_ajax_update2' ).click(
			function () {

				var check_id = new Array();
				var files = new Array();
				var form_names = $( "#selectmediaregister_forms" ).serializeArray();
				var j = 0;
				$.each(
					form_names,
					function (i) {
						if ( form_names[i].name.indexOf( "files" ) != -1 ) {
							files[j] = form_names[i].value;
							j = j + 1;
						}
					}
				);

				$( "#selectmediaregister-loading-container" ).empty();

				if ( 0 == files.length ) {
					$( "#selectmediaregister-loading-container" ).append( "<div class=\"notice notice-error is-dismissible\"><ul><li>" + bulkmediaregister_text.select_error + "</li></ul></div>" );
					return;
				}

				$( "#selectmediaregister-loading-container" ).append( "<div id=\"selectmediaregister-update-progress\"><progress value=\"0\" max=\"100\"></progress> 0%</div><button type=\"button\" id=\"selectmediaregister_ajax_stop\">" + bulkmediaregister_text.stop_button + "</button>" );
				$( "#selectmediaregister-loading-container" ).append( "<div id=\"selectmediaregister-update-result\"></div>" );
				var update_continue = true;
				/* Stop button */
				$( "#selectmediaregister_ajax_stop" ).click(
					function () {
						update_continue = false;
						$( "#selectmediaregister_ajax_stop" ).text( bulkmediaregister_text.stop_message );
					}
				);

				var count = 0;
				var success_count = 0;
				var error_count = 0;
				var error_update = "";

				$.each(
					files,
					function (i) {
						var j = i;
						selectmediaregister_defer = selectmediaregister_defer.then(
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
												'maxcount': files.length,
												'uid': bulkmediaregister_data.uid,
												'file': files[j],
											}
										}
									).then(
										function (result) {
											count += 1;
											success_count += 1;
											$( "#selectmediaregister-update-result" ).append( result );
											$( "#selectmediaregister-update-progress" ).empty();
											var progressper = Math.round( ( count / files.length ) * 100 );
											$( "#selectmediaregister-update-progress" ).append( "<progress value=\"" + progressper + "\" max=\"100\"></progress> " + progressper + "%" );
											if ( count == files.length || update_continue == false ) {
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
														$( "#selectmediaregister-update-progress" ).empty();
														$( "#selectmediaregister-update-progress" ).append( result );
														$( "#selectmediaregister_ajax_stop" ).hide();
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
