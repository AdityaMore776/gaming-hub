// Get the form and the message area
var form = document.getElementById("registerForm");
var message = document.getElementById("message");

// Run this when the Register button is clicked
form.addEventListener("submit", function (event) {
  // Stop the page from reloading
  event.preventDefault();

  var name = document.getElementById("name").value;
  var email = document.getElementById("email").value;
  var phone = document.getElementById("phone").value;
  var trip = document.getElementById("trip").value;
  var people = document.getElementById("people").value;

  // Check that every box is filled in
  if (name == "" || email == "" || phone == "" || trip == "" || people == "") {
    message.style.color = "red";
    message.textContent = "Please fill in all the fields.";
    return;
  }

  // Show a thank you message
  message.style.color = "green";
  message.textContent = "Thank you " + name + "! Your trip to " + trip + " for " + people + " person(s) is registered.";

  // Clear the form
  form.reset();
});
