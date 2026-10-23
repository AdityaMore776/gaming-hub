// ---------- Home page: "Surprise Me" button ----------
var surpriseButton = document.getElementById("surpriseButton");

if (surpriseButton) {
  surpriseButton.addEventListener("click", function () {
    var places = ["France", "Italy", "Switzerland", "Japan", "Thailand", "Dubai"];
    var randomNumber = Math.floor(Math.random() * places.length);
    document.getElementById("surpriseResult").textContent = "You should go to " + places[randomNumber] + "!";
  });
}

// ---------- Europe and Asia pages: "More Info" buttons ----------
var infoButtons = document.querySelectorAll(".info-button");

for (var i = 0; i < infoButtons.length; i++) {
  infoButtons[i].addEventListener("click", function () {
    // The details text is the next thing after the trip-top box
    var details = this.parentElement.nextElementSibling;

    if (details.style.display == "block") {
      details.style.display = "none";
      this.textContent = "More Info";
    } else {
      details.style.display = "block";
      this.textContent = "Less Info";
    }
  });
}

// ---------- Sign Up page: live price and booking ticket ----------
var signupButton = document.getElementById("signupButton");

if (signupButton) {
  var country = document.getElementById("country");
  var travellers = document.getElementById("travellers");

  // Work out the total price and show it
  function updatePrice() {
    var total = country.value * travellers.value;
    document.getElementById("totalPrice").textContent = total;
  }

  country.addEventListener("change", updatePrice);
  travellers.addEventListener("input", updatePrice);

  signupButton.addEventListener("click", function () {
    var fullName = document.getElementById("fullName").value;
    var passport = document.getElementById("passport").value;
    var error = document.getElementById("error");

    if (fullName == "" || passport == "" || travellers.value < 1) {
      error.textContent = "Please enter your name, passport number and at least 1 traveller.";
      return;
    }

    // Make a random booking ID like WW-4821
    var bookingId = "WW-" + Math.floor(1000 + Math.random() * 9000);
    var countryName = country.options[country.selectedIndex].text.split(" (")[0];

    document.getElementById("bookingId").textContent = bookingId;
    document.getElementById("ticketName").textContent = fullName;
    document.getElementById("ticketCountry").textContent = countryName;
    document.getElementById("ticketTotal").textContent = "$" + document.getElementById("totalPrice").textContent;

    // Hide the form and show the ticket
    document.getElementById("signupBox").style.display = "none";
    document.getElementById("ticket").style.display = "block";
  });
}
