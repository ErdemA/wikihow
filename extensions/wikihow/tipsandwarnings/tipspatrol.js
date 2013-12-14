var toolURL = "/Special:TipsPatrol";
var tipId;
var coachTip;
var articleId;
var nextTipData;
var LEADERBOARD_REFRESH = 10 * 60;

// Init shortcut key bindings
$(document).ready(function() {
	initToolTitle();
	var mod = Mousetrap.defaultModifierKeys;
	Mousetrap.bind(mod + 's', function() {$('#tip_skip').click();});
	Mousetrap.bind(mod + 'p', function() {$('#tip_keep').click();});
	Mousetrap.bind(mod + 'd', function() {$('#tip_delete').click();});
});

$('#tpc_next_button').click(function(e) {
	e.preventDefault();
	$('#tpc_results').slideUp('fast');
	showNextTip();
});

$('#tip_header').on( "click", "#tip_delete", function(e) {
	e.preventDefault();
	if (!jQuery(this).hasClass('clickfail')) {
		if (!coachTip) {
			clearTool();
		}
		$.post(toolURL, {
			deleteTip: true,
			tipId: tipId,
			coachTip: coachTip,
			articleId: articleId,
			tip: jQuery("#tip_tip").val()
			},
			function (result) {
				//updateStats();
				loadResult(result);
				incrementStats();
			},
			'json'
		);
	}
});


$('#tip_header').on('keyup', '#tip_tip', function(e) {
	$('#tip_read').attr('checked', true);
});

$('#tip_header').on( "click", "#tip_keep", function(e) {
	e.preventDefault();

	if (validate()) {
		if (!jQuery(this).hasClass('clickfail')) {
			if (!coachTip) {
				clearTool();
			}
			$.post(toolURL, {
				keepTip: true,
				articleId: articleId,
				tipId: tipId,
				coachTip: coachTip,
				tip: jQuery("#tip_tip").val()
				},
				function (result) {
					//updateStats();
					loadResult(result);
					incrementStats();
				},
				'json'
			);
		}
	}
});


function validate() {
	var tip = $.trim($('#tip_tip').val());
	var lastChar = tip.charAt(tip.length - 1);
	var validLastChar = lastChar == '.' || lastChar == '?' || lastChar == '!' || lastChar == '"' || lastChar == '}';
	if (!validLastChar) {
		alert('Oops! We noticed you were missing some punctuation at the end. Can you edit the tip before submitting?');
		$('#tip_tip').focus();
		return false;
	}

	if (!$('#tip_read').is(':checked')) {
		alert("Did you mean to publish this tip without editing it? If you did please check the box above the 'Skip' button.");	
		return false;
	}

	return true;
}

function skipTip() {
	if (!coachTip) {
		clearTool();
	}
	$.post(toolURL, {
		skipTip: true,
		tipId: tipId,
		coachTip: coachTip,
		articleId: articleId
		},
		function (result) {
			//updateStats();
			loadResult(result);
		},
		'json'
	);
}

$('#tip_header').on( "click", "#tip_skip", function(e) {
	e.preventDefault();
	if (!jQuery(this).hasClass('clickfail')) {
		skipTip();
	}
});

function updateWidgetTimer() {
	updateTimer('stup');
	window.setTimeout(updateWidgetTimer, 60*1000);
}

$(document).ready(function(){
	$("#article").prepend("<div id='tip_count' class='tool_count'><h3></h3><span>tips remaining</span></div>");
	skipTip();
	window.setTimeout(updateStandingsTable, 100);
	window.setTimeout(updateWidgetTimer, 60*1000);
});


function debugResult(result) {
	console.log("tipid: " + result['tipId']);
	console.log("articleId: " + result['articleId']);

	console.log("debug: ");
	for (i in result['debug']) {
		console.log(result['debug'][i]);
	}
}

function showCoachResult(info) {
	var answer = info['answer'];
	var score = info['score'];
	var fail_message = info['fail_message'];
	var success_message = info['success_message'];
	var userName = info['userName'];

	if (score == -1) {
		showNextTip();
		return;
	}

	$('#tpc_results').slideDown('fast');
	$('#tpc_real_name').html(" " + userName + "!");
	$('#tpc_heading_details').html("I am here to help you learn about patrolling tips. The last tip was inserted by me to help you become a better Tips Patroller.");
	$('#tpc_img').removeClass();
	$('#tpc_message_details').empty();
	$('#tpc_message_header').empty();
	$('#tpc_border').removeClass();

	var button_incorrect = "Delete";
	var button_correct = "Publish";
	if (answer == 1) {
		button_incorrect = "Publish";
		button_correct = "Delete";
	}
	if (score == 0) {
		// answered wrong
		$('#tpc_border').addClass('tpc_background_incorrect');
		$('#tpc_img').addClass('tpc_incorrect');
		$('#tpc_answer_header').html('Oops!');
		$('#tpc_answer_details').html('You pressed the "'+button_incorrect+'" button. A better choice would have been the "'+button_correct+'" button.');
		if (fail_message) {
			$('#tpc_message_details').html(fail_message);
			$('#tpc_message_header').html("Details:");
		}
	}
	if (score == 1) {
		// answered correctly
		$('#tpc_border').addClass('tpc_background_correct');
		$('#tpc_img').addClass('tpc_correct');
		$('#tpc_answer_header').html('Congrats!');
		$('#tpc_answer_details').html('You pressed the "'+button_correct+'" button.');
		if (success_message) {
			$('#tpc_message_details').html(success_message);
			$('#tpc_message_header').html("Why your choice was correct:");
		}
	}
}

function loadResult(result) {
	debugResult(result);
	nextTipData = result;

	$("#tip_waiting").hide();

	if (result['coachResult']) {
		console.log("coachresult with score:" + result['coachResult']['score']);
		showCoachResult(result['coachResult']);
	} else {
		showNextTip();
	}
}

function showNextTip() {
	result = nextTipData;
	if(result['coaching'] == true) {
		console.log("this is a coaching tip");
	}
	coachTip = result['coaching'];

	if (result['error']) {
		$("#tip").hide();
		$("#tip_error").show();
		$(".tool_count").hide();
		$("#tip_read").hide();
	}
	else {
		$("#tip_article").html(result['article']);
		$("#tip_tip").val(result['tip']).focus();
		$('#tip_read').attr('checked', false);
		$("h1.firstHeading").html($("<a/>").attr('href',result['articleUrl']).attr('target', '_blank').text(result['articleTitle']));
		tipId = result['tipId'];
		articleId = result['articleId'];
		$("#tip_header a").removeClass("clickfail");
		setCount(result['tipCount']);
	}
}

function setCount(count) {
	$(".tool_count h3").fadeOut(400, function() {
		$(".tool_count h3").html(count).fadeIn();
	});
}

function clearTool() {
	$("#tip_waiting").show();
	$("#tip_article").html("");
	$("h1.firstHeading").text("Tips Patrol");
	$("#tip_header a").addClass("clickfail");
}

updateStandingsTable = function() {
	var url = '/Special:Standings/TipsPatrolStandingsGroup';
	$.get(url, function (data) {
		$('#iia_standings_table').html(data['html']);
	}, 'json');
	$("#stup").html(LEADERBOARD_REFRESH / 60);
	window.setTimeout(updateStandingsTable, 1000 * LEADERBOARD_REFRESH);
}

function incrementStats() {
	var statboxes = '#iia_stats_today_tiptool_indiv1,#iia_stats_week_tiptool_indiv1,#iia_stats_all_tiptool_indiv1,#iia_stats_group';
	$(statboxes).each(function(index, elem) {
			$(this).fadeOut(function () {
				var cur = parseInt($(this).html());
				$(this).html(cur + 1);
				$(this).fadeIn();
			});
	});
}
