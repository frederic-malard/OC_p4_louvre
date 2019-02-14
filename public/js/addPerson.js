$index = 0

$('#addPerson').click(function(){
    $('#reservation_temporaryPersonsList').append($('#reservation_temporaryPersonsList').data('prototype').replace(/__name__/g, $index));
    handleDeleteButtons();
    $index++;
});

function handleDeleteButtons()
{
    $('button[data-action="delete"]').click(function(){
        target = this.dataset.target;
        $(target).remove();
    });
}

handleDeleteButtons();