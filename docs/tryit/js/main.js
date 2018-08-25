var terminal = new Terminal();
$('#console').append(terminal.html);

terminal.print("vowserDB Terminal");

// terminal.input("Enter something plz", function(cmd) {
//     terminal.print("You entered " + cmd);
// });

// terminal.confirm("You want that?", function(ans) {
//     terminal.print("Seems like " + ans);
// });

// terminal.password("Enter secure password", function(pass) {
//     terminal.print("LOL, you think " + pass + " is  secure?");
// });

// terminal.clear();
terminal.input('', () => {});