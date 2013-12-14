RCTestObj = null;
RCTestObj = {};

jQuery.extend(RCTestObj, (function ($) {
	function RCTest() {
		// Button Constants
		var RESP_QUICKNOTE = 1;
		var RESP_QUICKEDIT = 2;
		var RESP_ROLLBACK = 3;
		var RESP_SKIP = 4;
		var RESP_PATROLLED = 5;
		var RESP_THUMBSUP = 6;
		var RESP_LINK = 7;

		this.init = function() {
			addEventHandlers();
			fakeRevisionTime();
			removeNextLink();
			// If we're in a debugging mode, add some debug info
			if (extractParamFromUri(document.location.search, 'rct_mode')) {
				addDebugInfo();
			}
		}

		function addEventHandlers() {
			// Handle all the submenu links and diff heading links that could let people make edits to the diff.
			// We don't want people to mistakenly try to edit this diff thinking it's the most current revision.
			$('#rc_advanced a, .diff-otitle a, .diff-ntitle a').unbind('click').click(function(e) {
				e.preventDefault();
				handleQuiz(RESP_LINK);
			});

			$('#qn_button').attr('onclick', '').unbind('click').click(function(e) {
				e.preventDefault();
				handleQuiz(RESP_QUICKNOTE);
			});

			$('#qe_button').attr('onclick', '').unbind('click').click(function(e) {
				e.preventDefault();
				handleQuiz(RESP_QUICKEDIT);
			});

			$('#rb_button').attr('onclick', '').unbind('click').click(function(e) {
				e.preventDefault();
				handleQuiz(RESP_ROLLBACK);
			});

			$('#skippatrolurl').attr('onclick', '').unbind('click').click(function(e) {
				e.preventDefault();
				handleQuiz(RESP_SKIP);
			});

			$('#markpatrolurl').attr('onclick', '').unbind('click').click(function(e) {
				e.preventDefault();
				handleQuiz(RESP_PATROLLED);
			});

			$('#gb_button').attr('onclick', '').unbind('click').click(function(e) {
				e.preventDefault();
				goback();
			});

			$('.thumbbutton').unbind('click').click(function(e) {
				e.preventDefault();
				handleQuiz(RESP_THUMBSUP);
			});
		}

		function addDebugInfo() {
			$("<div id='rct_debug' />").html('DEBUG: Test id ' + $("#rct_data").html()).insertAfter("#rct_data");	
		}

		function removeNextLink() {
			$('#differences-nextlink').remove();
		}

		// Fake the time of the diff revisions
		function fakeRevisionTime() {
			var d = new Date();
			// Use seconds as the unit.  A little more random than minutes. And keep the unit less than 10
			// to better emulate actual diff times
			var mins  = d.getSeconds() % 10;
			// Make sure unit is at least two so we can make the string plural
			if (mins  < 2) {
				mins = 2;
			}
			var ago = mins + ' minutes ago';
			$('#mw-diff-ntitle2').html(ago);

			// Do something similar to the old diff timing, but make it in hours
			var hours = (d.getSeconds() * d.getMinutes()) % 24;
			if (hours < 2) {
				hours = 2;
			}
			ago = hours + ' hours ago';
			$('#mw-diff-otitle2').html(ago);

			$('#mw-diff-ntitle1 a').first().html('Current revision');
			$('#bodycontents2 > h2:first').html('Current revision');
		}

		function handleQuiz(response) {
			var testId = $('#rct_data').html();
			var url = '/Special:RCTestGrader?id=' + testId + '&response=' + response;

			// If we're debugging, let the special page know
			var mode = extractParamFromUri(document.location.search, 'rct_mode');
			if (mode) {
				url += "&rct_mode=" + mode;
			}

			//alert(url);
			$.get(url, function(data) {
				$('#rct_results').html(data).slideDown('fast');
				// Close the rc submenu if it's open
				$('.rc_submenu').hide();
				// Add an event listener to the button that is added as a result of the /Special:RCTestGrader call
				$('#rct_next_button').unbind('click').click(function(e) {
					e.preventDefault();
					//skip(); // from rcpatrol.js
					//$('#rct_results').slideUp('fast');
					window.location.reload();
				});
			});
		}
	}

	// Initialize the RCTest object
	var rcTest =  new RCTest();
	rcTest.init();
	return rcTest;

})(jQuery) );
