
// ele: stopwatch dom
function stopwatch(ele) {
	// timing variables
	this.start_time = new Date();
	this.interval_id = 0;
	this.accumu_diff = 0;
	this.session_diff = 0;
	
	// ele variables
	this.start_btn = ele.getElementsByClassName('start')[0];
	this.start_btn.reverse = this;
	this.clear_btn = ele.getElementsByClassName('clear')[0];
	this.clear_btn.reverse = this;
	this.elapsed = ele.getElementsByClassName('elapsed')[0];
	this.millisecond = ele.getElementsByClassName('millisecond')[0];
	this.id = ele.id;
	
	// callbacks
	this.start_btn_cb = function(e) {
		e.preventDefault();
		var sw = this.reverse;
        if (sw.start_btn.innerHTML === "Start") {
			//sw.accumu_diff = sw.ajax('fetch', sw.id, "0");		
            sw.start_time = new Date();
			// sw.interval_id = setInterval(sw.update(sw), 1); will not work, cause of optimization.
            sw.interval_id = setInterval(function() {return sw.update(sw)}, 1);
            sw.start_btn.innerHTML = "Pause";
        } else if (sw.start_btn.innerHTML === "Pause") {
            clearInterval(sw.interval_id);
            sw.accumu_diff = sw.accumu_diff + sw.session_diff;
			
			sw.ajax('store', sw.id, "" + sw.accumu_diff);
			
            sw.start_btn.innerHTML = "Continue";
        } else {
            sw.start_time = new Date();
            sw.interval_id = setInterval(function() {return sw.update(sw)}, 1);
            sw.start_btn.innerHTML = "Pause";
        }
	};
	
	this.clear_btn_cb = function(e) {
		e.preventDefault();
		var sw = this.reverse;
        clearInterval(sw.interval_id);
        sw.elapsed.innerHTML = "00:00:00";
        sw.millisecond.innerHTML = "0";
        sw.start_btn.innerHTML = "Start";
		sw.accumu_diff = 0;
		
		sw.ajax('store', sw.id, "" + sw.accumu_diff);
	};
	
	this.update = function(sw) {
		"use strict";
		var now, difference;
		now = new Date();
		sw.session_diff = now.getTime() - sw.start_time.getTime();
		difference = sw.session_diff + sw.accumu_diff;
		sw.modify(sw, difference);
	};
	
	this.modify = function(sw, difference) {
		var second, minute, hour;
		
		sw.millisecond.innerHTML = (difference % 1000).toString();
		
		difference = Math.floor(difference / 1000);
		second = (difference % 60).toString();
		if (second.length === 1) {
			second = "0" + second;
		}
		difference = Math.floor(difference / 60);
		minute = (difference % 60).toString();
		if (minute.length === 1) {
			minute = "0" + minute;
		}
		difference = Math.floor(difference / 60);
		hour = difference.toString();
		if (hour.length === 1) {
			hour = "0" + hour;
		}
		
		sw.elapsed.innerHTML = hour + ":" + minute + ":" + second;
	};
	
	this.ajax = function(cmdstr, watchid, timestamp) {
		var ts = 0;
		jQuery.ajax({
			url: DOKU_BASE + 'lib/exe/ajax.php',
			type: 'POST',
			async: false,
			data: {
				call: 'plugin_stopwatch',
				wid: watchid,
				ts: timestamp,
				cmd: cmdstr,
				id: JSINFO.id
			},
			success: function (data) {
				ts = parseInt(data.ts);
			},
			error: function (xhr, status, error) {
				alert('fuckyou');
			}
		});
		return ts;	
	};
	
	this.init = function() {
		this.accumu_diff = this.ajax('fetch', this.id, "0");
		this.modify(this, this.accumu_diff);
	};
}

var stopwatches = new Array();

window.onload = function () {
    "use strict";
    var elapsed, millisecond, start, clear;
    if (!document.addEventListener) {
        alert("Your browser does not support addEventListener\nStopWatch unable to function");
        return;
    }
	
	var sws = document.getElementsByClassName("stopwatch");
	for(var i=0; i < sws.length; i++) {
		var sw = new stopwatch(sws[i]);
		sw.start_btn.addEventListener("click", sw.start_btn_cb, false);
		sw.clear_btn.addEventListener("click", sw.clear_btn_cb, false);
		sw.init();
		stopwatches[i] = sw;
	}
};

window.onbeforeunload = function(e) {
	"use strict";
	var flag = 0;
　　for(var i=0; i < stopwatches.length; i++) {
		if(stopwatches[i].start_btn.innerHTML === "Pause") {
			flag = 1;
		}
	}
	if(flag == 1) {
		var e = window.event || e;
		// chrome only display default information.
　　	e.returnValue=("尚有计时器未暂停，确定离开？");
	}
};
