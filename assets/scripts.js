const connectionToken = "Bearer " + daily_co_script.apikey;

// Join a room
function joinRoom(meetingID) {
	const callFrame = window.DailyIframe.createFrame({
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

	// Listen for when we leave the room so we can distroy the iFrame
	callFrame.on('left-meeting', (evt) => {
		callFrame.destroy();
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
 Debugging Functions
 Author: Thomas McMahon

 These functions are mainly used for debugging purposes.
 ------------------------------------------------------------------------ */

// List the rooms on the homepage
if ( daily_co_script.debug ) {
	getRooms();
}

// List all rooms
function getRooms(roomStatus) {
	console.log('room debugging');

	// check to ensure the #rooms DIV is on the page
	const roomDiv = document.getElementById("rooms");

	if ( roomDiv ) {
		const data = null;
		const xhr  = new XMLHttpRequest();

		xhr.addEventListener("readystatechange", function () {
			if (this.readyState === this.DONE) {

				let ourRooms = '';
				let roomsJSON = JSON.parse(this.responseText);
				for (let i = 0; i < roomsJSON.data.length; i++) {
					let response = roomsJSON.data[i];
					ourRooms = ourRooms + '<li><a href=' + response.url + '>' + response.name + '</a> <button class="delete" onclick="deleteRoom(`' + response.name + '`)">Delete Room</button> <button class="join" onclick="joinRoom(`' + response.url + '`)">Join Room</button> EXP Date: ' + convert_date(response.config.exp) + '</li>';
				}

				roomDiv.innerHTML = '<ul>' + ourRooms + '</ul>';

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
	return date;

}

// Delete Rooms
function deleteRoom(roomName) {
	const data = null;

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
}
