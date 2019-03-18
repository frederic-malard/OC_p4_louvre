$index = 0

$('#addPerson').click(function(){
    $('#reservation_persons').append($('#reservation_persons').data('prototype').replace(/__name__/g, $index));
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