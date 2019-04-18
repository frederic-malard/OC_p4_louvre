// included in templates > booking > index.html.twig
// adding or deleting persons to the reservation, in the reservation form, using "ajoutez un visiteur" and "supprimer le visiteur" buttons

// we create an index, that we will use to create a unique html id for the person we add to the reservation.
$index = 0

/**
 * search the delete buttons related to the persons
 * associate to it a function that remove the person
 * note : data action and target are defined in templates > booking > index.html.twig in block _reservation_persons_entry_row
 */
function handleDeleteButtons()
{
    $('button[data-action="delete"]').click(function(){
        target = this.dataset.target;
        $(target).remove();
    });
}

/*
 * trigged when clicking on "ajouter un visiteur" button in the reservation form
 * we add to the collection of persons (that is in the reservation) all the code usefull to create a new person, code we can found in the data prototype of the collection.
 * then we include the unique index in the html id of the person
 * then we call the handledeletebuttons function defined below
*/
$('#addPerson').click(function(){
    $('#reservation_persons').append($('#reservation_persons').data('prototype').replace(/__name__/g, $index));
    handleDeleteButtons();
    $index++;
});

// we call the function before clicking on any buttons to get sure every delete button is handled
handleDeleteButtons();