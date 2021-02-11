
var getUrl = window.location;
console.log(getUrl.protocol + '//' + getUrl.host + "/api/users/sugestion");

srcImg = getUrl.protocol + "//" + getUrl.host + "/img/icons/user.png";
$.ajax({
    url: getUrl.protocol + '//' + getUrl.host + "/api/users/sugestion",
    type:'GET',
    success: function (response) {
        response.users.forEach((user)=>{

            let elmente = '<div class="RechArtisteItems">'+
                '<img src="'+srcImg+'" alt="">'+
                '<a href="'+getUrl.protocol + '//' + getUrl.host + "/api/users/profil/"+user.id+'">'+user.nom +' '+ user.prenom +'</a>'+
                '<button id="u-'+user.id+'" >Ajouter</button>'+
                '</div>';
            $('#containerListArtiste').append(elmente);
            $('#u-'+user.id).on('click', function (e) {
                e.preventDefault();
                buttonLibelle = $('#'+e.target.id).text();
                if(buttonLibelle == "Ajouter"){
                    $.ajax({
                        url: getUrl.protocol + '//' + getUrl.host + "/api/users/abonner/" + e.target.id.substring(2),
                        type: 'GET',
                        success: function (response) {
                            $('#'+e.target.id).html('Supprimer');
                        }
                    })
                }else {
                    $.ajax({
                        url: getUrl.protocol + '//' + getUrl.host + "/api/users/deabonner/" + e.target.id.substring(2),
                        type: 'GET',
                        success: function (response) {
                            $('#'+e.target.id).html('Ajouter');
                        }
                    })
                }
            })
        });
    }
});
function cleanerAbonement(){
    $.ajax({
        url: getUrl.protocol + '//' + getUrl.host +'/api/users/abonnement/list',
        type:'GET',
        success:function (response) {
            response.listAbonnement.forEach(function (user) {
                $('#u-'+user.id).html('Supprimer');
            })
        },
        error:()=>{
            console.log('prob in cleanAbonnement');
        }
    });
}
cleanerAbonement();
/* clean user already in abonement*/

function getUsers(){
    $.ajax({
        url: getUrl.protocol + '//' + getUrl.host + "/api/users/sugestion",
        type: 'GET',
        succss: function (response) {
            console.log(response);
            response.users.forEach(function (user) {
                srcImg = getUrl.protocol + "//" + getUrl.host + "/img/icons/user.png";
                elementUser  = '<div class="RechArtisteItems">'+
                    '<img src="'+srcImg+'" alt="">'+
                    '<p>'+user.nom +' '+ user.prenom +'</p>'+
                    '<a id=u-"'+user.id+'">Ajouter</a>'+
                '</div>';
                $('#containerListArtiste').append(elementUser);
                $('#u-' + user.id).on('click', function (e) {
                    e.preventDefault();
                    idElement = e.target.id.substring(2);
                    console.log($('#'+e.target.id).text());
                    if($('#'+e.target.id).text() == 'Ajouter'){
                        $.ajax({
                            url: getUrl.protocol + '//' + getUrl.host + "/api/users/abonner/" + idElement,
                            type: 'GET',
                            success: function (response) {
                                console.log('probleme abonnement');
                            },
                            error: function (e) {
                                console.log('probleme abonnement');
                            }
                        });
                    }else {
                        $.ajax({
                            url: getUrl.protocol + '//' + getUrl.host + "/api/users/deabonner/" + idElement,
                            type: 'GET',
                            success: function (response) {
                                console.log('probleme abonnement');
                            },
                            error: function (e) {
                                console.log('probleme abonnement');
                            }
                        });
                    }
                    $(e.target.id).html('supprimer');

                });
            });
        },
        error: function (response) {
            console.log("probleme de chargement");
        }
    });
}