const connectionToken = "Bearer " + daily_co_script.apikey;

// Join a room
function joinRoom(meetingID) {
	// add the placeholder iFrame
	const iFrameHolder = document.getElementById("dailyco_call_iframe_wrap");
	iFrameHolder.innerHTML = '<iframe class="dailyco_call_frame" id="dailyco_call_frame" title="daily.co call frame" allow="camera; microphone; autoplay; display-capture" style="position: fixed; top: 0px; left: 0px; width: 100%; height: 100%;"></iframe>';

	const callFrame = window.DailyIframe.wrap(document.getElementById("dailyco_call_frame"),{
		showLeaveButton: true,
		iframeStyle: {
			position: 'fixed',
			top: 0,
			left: 0,
			width: '100%',
			height: '100%'
		}
	});
	callFrame.join({ url: meetingID });

	// Add a class to ensure items don't show above the video.
	document.body.classList.add('dailyco-active')

	// Listen for when we leave the room so we can distroy the iFrame
	callFrame.on('left-meeting', (evt) => {
		callFrame.destroy();
		document.body.classList.remove('dailyco-active')
	})
}

// Create a new room
function createRoom(callback) {
	// Room Expieration Time
	let expDate = new Date();
	expDate.setHours(expDate.getHours()+24);
	// Convert to Unix Time
	let expDateUnix = Date.parse(expDate)/1000;

	const data = "{\"properties\":{\"exp\":" + expDateUnix + ",\"autojoin\":true},\"privacy\":\"public\"}";

	const xhr = new XMLHttpRequest();

	xhr.addEventListener("readystatechange", function () {
		if (this.readyState === this.DONE) {

			const response = JSON.parse(this.responseText);
			callback(response.url);
		}
	});

	xhr.open("POST", "https://api.daily.co/v1/rooms");
	xhr.setRequestHeader("content-type", "application/json");
	xhr.setRequestHeader("authorization", connectionToken);

	xhr.send(data);
}

// Validate Form
function validateDailyCoForm(e) {
	e.preventDefault();

	const fieldName = document.forms['dailycoForm'].elements['name'];
	const fieldEmail = document.forms['dailycoForm'].elements['email'];

	let fieldEmailValid = fieldEmail.checkValidity();
	fieldEmail.reportValidity();

	let fieldNameValid = fieldName.checkValidity();
	fieldName.reportValidity();

	if (true === fieldNameValid && true === fieldEmailValid ) {
		let formName = fieldName.value;
		let formEmail = fieldEmail.value;
		getEmailData(formName, formEmail);
	}
}

// Get email data
function getEmailData(formName, formEmail) {

	createRoom(function (roomURL) {
		var data = {
			action: 'dailyco_email',
			name: formName,
			email: formEmail,
			link: roomURL,

		};

		//it's easiest with jQuery
		jQuery.post(
			daily_co_script.ajaxurl,
			data,
		);

		// join the meeting you just create
		joinRoom(roomURL);

	});
}

// Add event listener to submit button to send off the email
const submitButton = document.getElementById("createRoom");

if ( submitButton ) {
	submitButton.addEventListener("click", validateDailyCoForm, false);
}

/* ---------------------------------------------------------------------
 Admin Functions
 Author: Thomas McMahon
 ------------------------------------------------------------------------ */

// List the rooms in the admin
getRooms();

// List all rooms
function getRooms(roomStatus) {

	// check to ensure the #rooms DIV is on the page
	const roomDiv = document.getElementById("rooms");

	if ( roomDiv && daily_co_script.apikey ) {
		console.log('room debugging');

		const data = null;
		const xhr  = new XMLHttpRequest();

		xhr.addEventListener("readystatechange", function () {
			if (this.readyState === this.DONE) {

				let ourRooms = '';
				let roomsJSON = JSON.parse(this.responseText);
				for (let i = 0; i < roomsJSON.data.length; i++) {
					let response = roomsJSON.data[i];
					ourRooms = ourRooms + '<tr><td><a href=' + response.url + '>' + response.name + '</a></td><td>Room Expires On ' + convert_date(response.config.exp) + '</td><td><button class="delete" onclick="return deleteRoom(`' + response.name + '`)">Delete Now</button></td></tr>';
				}

				roomDiv.innerHTML = '<table role="presentation" class="wp-list-table widefat fixed striped">' + ourRooms + '</table>';

				if (roomStatus === 'new') {
					let text = document.getElementById('rooms');
				}
			}

		});

		xhr.open("GET", "https://api.daily.co/v1/rooms");
		xhr.setRequestHeader("authorization", connectionToken);

		xhr.send(data);
	}
}

// Convert date from unix to readable
function convert_date(unixdate) {
	let unix_timestamp = unixdate
	let date = new Date(unix_timestamp * 1000);
	let yyyy = date.getFullYear();
	let mm = ('0' + (date.getMonth() + 1)).slice(-2);  // Months are zero based. Add leading 0.
	let dd = ('0' + date.getDate()).slice(-2);       // Add leading 0.
	let hh = date.getHours();
	let h = hh;
	let min = ('0' + date.getMinutes()).slice(-2);     // Add leading 0.
	let ampm = 'AM';

	if (hh > 12) {
		h = hh - 12;
		ampm = 'PM';
	} else if (hh === 12) {
		h = 12;
		ampm = 'PM';
	} else if (hh == 0) {
		h = 12;
	}

	// ie: 07/07/2020 @ 2:07 PM
	let time = mm + '/' + dd + '/' + yyyy + ' @ ' + h + ':' + min + ' ' + ampm;

	return time;

}

// Delete Rooms
function deleteRoom(roomName) {
	const data = null;

	var confirmDelete = confirm("Are you sure you want to delete?");
	if ( !confirmDelete ) {
		return false;
	}

	const xhr = new XMLHttpRequest();

	xhr.addEventListener("readystatechange", function () {
		if (this.readyState === this.DONE) {
			getRooms('deleted');
		}
	});

	let roomToDelete = "https://api.daily.co/v1/rooms/" + roomName;
	xhr.open("DELETE", roomToDelete);
	xhr.setRequestHeader("authorization", connectionToken);

	xhr.send(data);

	return false;
}
