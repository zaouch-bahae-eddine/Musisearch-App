//Liste des musiques

data = null;
state = null;
$.when($.ajax({
    url:'/api/accueil',
    method: 'GET',
    async: true,
    success: function(response){
        data = response;
        console.log(data);

    },
    error: function (response) {
        console.log('prob');
    }
})).then(function( dataa, textStatus, jqXHR ) {
    data = dataa["listMusicAccueil"];
    state = {
        played: false,
        volume: 0.5,
        muted: false,
        repeated: false,
        idMusic: Object.keys(data),
        currentId: 0,
    };


    /* Variables globales */
    console.log(data[state.idMusic[state.currentId]].son);
    let audio = new Audio(data[state.idMusic[state.currentId]].son);
    audio.preload = "metadata";
    audio.volume = state.volume;
    let timeTrigger = null;
    let fullProgressBarPurcent = 0;
    let fullProgressBarPurcentVolume = 0;
    audio.addEventListener('ended', ()=>{
        stopTimeReading();
        currentTimeTimer.innerHTML = formatTime(0);
        progressBarRef.firstElementChild.style.width = "0";
        if (!state.repeated) {
            nextAudio();
        }
        switchPlay();
    });

    let downloadMusicRef = document.getElementById("DownloadMusic");
    downloadMusic = (son) => {
        downloadMusicRef.setAttribute('href', son);
        //downloadMusicRef.setAttribute('download', son);
    }
    downloadMusic(data[state.idMusic[state.currentId]].son);
    /* Variables globales */


    //referance des ellemnt html

    let imgMusic = document.getElementById("imgMusic");
    let LoadingMusic = document.getElementById("LoadingMusic");
    let TitleMusic = document.getElementById("TitleMusic");
    let Artist = document.getElementById("Artist");
    let DownloadMusic = document.getElementById("DownloadMusic");
    let Favorit = document.getElementById("Favorit");
    let Previose = document.getElementById("Previose");
    let PlayPause = document.getElementById("PlayPause");
    let Next = document.getElementById("Next");
    let Repeat = document.getElementById("Repeat");
    let VolumeVerrouRef = document.getElementById("VolumeVerrou");
    let progressBarVolumeRef = document.getElementById("progressBarVolume");
    let volumeIconRef = document.getElementById("volumeIcon");
    let currentTimeTimerRef = document.getElementById("currentTimeTimer");
    let durationTimerRef = document.getElementById("durationTimer");
    let fullScreenVerrouRef = document.getElementById("fullScreenVerrou");
    let progressBarRef = document.getElementById("progressBar");

    let fullScreenVerrou = fullScreenVerrouRef;
    let progressBar = progressBarRef;
    let durationTimer = durationTimerRef;
    let currentTimeTimer = currentTimeTimerRef;
    let progressBarVolume = progressBarVolumeRef;
    let VolumeVerrou = VolumeVerrouRef;
    let volumeIcon = volumeIconRef;
    let VolumeHideRef = null;

    //referance des ellemnent html



    switchPlay = () => {

        state.played = ! state.played;
        if(audio.paused){
            audio.play();
            startTimeReading();
            PlayPause.firstElementChild.src = "../img/AudioControllerIcons/pause.png";
            LoadingMusic.style.display = "block";
        }
        else {
            audio.pause();
            stopTimeReading();
            PlayPause.firstElementChild.src = "../img/AudioControllerIcons/play.png";
            LoadingMusic.style.display = "none";
        }
    }
    next = () => {

        $('#m-'+state.idMusic[state.currentId]).removeClass('selected');
        if(state.idMusic.length-1 > state.currentId){
            state.currentId++;
        } else {
            state.currentId = 0;
        }
        $('#m-'+state.idMusic[state.currentId]).addClass('selected');
        if(!data[state.idMusic[state.currentId]].favorit){
            Favorit.src = "../img/AudioControllerIcons/heart.png"
        } else {
            Favorit.src = "../img/AudioControllerIcons/heart-full.png"
        }
        audio = new Audio(data[state.idMusic[state.currentId]].son);
        audio.preload = "metadata";
        audio.volume = state.volume;
        imgMusic.src = data[state.idMusic[state.currentId]].img;
        audio.addEventListener('loadedmetadata', ()=>{
            durationTimerRef.innerHTML = formatTime(audio.duration);
        });
        audio.addEventListener('ended', ()=>{
            stopTimeReading();
            currentTimeTimer.innerHTML = formatTime(0);
            progressBarRef.firstElementChild.style.width = "0";
            if (!state.repeated) {
                nextAudio();
            }
            switchPlay();
        });

        downloadMusic(data[state.idMusic[state.currentId]].son);
    }
    previous = () => {

        $('#m-'+state.idMusic[state.currentId]).removeClass('selected');
        if(state.currentId > 0 ){
            state.currentId--;
        } else {
            state.currentId = state.idMusic.length - 1;
        }
        $('#m-'+state.idMusic[state.currentId]).addClass('selected');
        audio.pause();
        if(!data[state.idMusic[state.currentId]].favorit){
            Favorit.src = "../img/AudioControllerIcons/heart.png"
        } else {
            Favorit.src = "../img/AudioControllerIcons/heart-full.png"
        }
        audio = new Audio(data[state.idMusic[state.currentId]].son);
        audio.preload = "metadata";
        audio.volume = state.volume;
        imgMusic.src = data[state.idMusic[state.currentId]].img;
        audio.addEventListener('loadedmetadata', ()=>{
            durationTimerRef.innerHTML = formatTime(audio.duration);
        });
        audio.addEventListener('ended', ()=>{
            stopTimeReading();
            currentTimeTimer.innerHTML = formatTime(0);
            progressBarRef.firstElementChild.style.width = "0";
            if (!state.repeated) {
                nextAudio();
            }
            switchPlay();
        });
        downloadMusic(data[state.idMusic[state.currentId]].son);
    }
    nextAudio = () => {
        audio.pause();
        state.played = false;
        PlayPause.firstElementChild.src = "../img/AudioControllerIcons/play.png";
        LoadingMusic.style.display = "none";
        next();
        progressBar.firstElementChild.style.width = 0;
        currentTimeTimer.innerHTML = formatTime(0);
    }
    previousAudio = () => {
        if(audio.currentTime < 3) {
            previous();
        }
        PlayPause.firstElementChild.src = "../img/AudioControllerIcons/play.png";
        LoadingMusic.style.display = "none";
        audio.pause();
        audio.currentTime = 0;
        progressBar.firstElementChild.style.width = "0%";
        currentTimeTimer.innerHTML = formatTime(0);

        state.played = false;
    }

    startTimeReading = () => {
        timeTrigger = setInterval(() => {
            if(currentTimeTimer != null)
                currentTimeTimer.innerHTML = formatTime(audio.currentTime);
            fullProgressBarPurcent = (audio.currentTime * 100 / audio.duration) ;
            if(currentTimeTimer != null)
                progressBar.firstElementChild.style.width = fullProgressBarPurcent + "%";
        }, 100);
    }
    stopTimeReading = () => {
        clearInterval(timeTrigger);
    }
    formatTime = (time) => {
        if (isNaN(time))
            return '--:--';
        var min = Math.floor(time / 60);
        var sec = Math.floor(time % 60);
        return min + ':' + ((sec<10) ? ('0' + sec) : sec);
    }


    /* DRAG AND DROP LOGIC */
    let active = false;
    progressUpdate = (e) => {
        let progressBarWidth = progressBar.clientWidth;
        if (e.type === "touchstart" || e.type === "touchmove")
            fullProgressBarPurcent  = (((e.touches[0].clientX - progressBar.offsetLeft - 3)*100) / progressBarWidth);
        else
            fullProgressBarPurcent = (((e.clientX - progressBar.offsetLeft - 3)*100) / progressBarWidth);
        progressBar.firstElementChild.style.width = fullProgressBarPurcent > 0 ? (fullProgressBarPurcent + "%") : "0" ;
    }
    dragStart = (e) => {
        stopTimeReading();
        progressUpdate(e);
        audio.pause();
        audio.currentTime = (audio.duration * fullProgressBarPurcent) / 100;
        currentTimeTimer.innerHTML = formatTime(audio.currentTime);
        fullScreenVerrou.style.display = "block";
        active = true;
    }
    drag = (e) => {
        if(active) {
            progressUpdate(e);
            currentTimeTimer.innerHTML = formatTime(audio.currentTime);
            audio.currentTime = (audio.duration * fullProgressBarPurcent) / 100;
        }
    }
    dragEnd = (e) => {
        active = false;
        fullScreenVerrou.style.display = "none";
        audio.currentTime = (audio.duration * fullProgressBarPurcent) / 100;
        currentTimeTimer.innerHTML = formatTime(audio.currentTime);
        if(state.played) {
            audio.play();
            startTimeReading();
        }
    }
    /*Volume Logic */
    repeat = () => {
        state.repeated = !state.repeated;
        if(state.repeated){
            Repeat.src = "../img/AudioControllerIcons/repeatFull.svg";
        } else {
            Repeat.src = "../img/AudioControllerIcons/repeat.svg";
        }
    }
    muteVolume = () => {
        if(audio.volume != 0){
            audio.volume = 0;
            state.muted = true;
            progressBarVolume.style.visibility = "hidden";
        } else {
            audio.volume = state.volume;
            state.muted = false;
        }
        if(state.muted){
            volumeIcon.src = "../img/AudioControllerIcons/mute.svg";
        } else {
            volumeIcon.src = "../img/AudioControllerIcons/volume.svg";
        }
    }
    progressBarVolumeShow = (e) => {

        clearTimeout(VolumeHideRef);
        if (progressBarVolume.style.visibility == "" ||
            progressBarVolume.style.visibility == "hidden" ) {
            let p = e.target.getBoundingClientRect();
            progressBarVolume.style.top = p.top - 35 +'px';
            progressBarVolume.style.left = p.left - 22 +'px';
        }
        progressBarVolume.style.visibility = "visible";
    }
    progressBarVolumeHide = () => {
        VolumeHideRef = setTimeout(
            () => {
                progressBarVolume.style.visibility = "hidden";
            }
            ,1000);
    }
    VolumeProgressUpdate = (e) => {
        let position = progressBarVolume.getBoundingClientRect();
        const raport = position.bottom - position.top;
        let postionRaport = -(e.clientY - position.bottom);
        if (postionRaport <= 0) {
            fullProgressBarPurcentVolume = 0;
        } else if (postionRaport >= position.top){
            fullProgressBarPurcentVolume = 100;
        } else {
            fullProgressBarPurcentVolume = postionRaport * 100 / raport;
        }
        progressBarVolume.firstElementChild.style.width = fullProgressBarPurcentVolume >100 ? "100%" : fullProgressBarPurcentVolume + "%";
        audio.volume = fullProgressBarPurcentVolume / 100 > 1? 1: fullProgressBarPurcentVolume / 100;
        state.volume = audio.volume;
    }
    let activeVolume = false;
    dragStartVolume = (e) => {
        VolumeVerrou.style.display = "block";
        VolumeProgressUpdate(e);
        activeVolume = true;
    }
    dragVolume = (e) => {
        if(activeVolume) {

            VolumeProgressUpdate(e);
            clearTimeout(VolumeHideRef);
        }
    }
    dragEndVolume = (e) => {
        activeVolume = false;
        VolumeVerrou.style.display = "none";
        state.muted = false;
        progressBarVolumeHide();
    }

    /* DRAG AND DROP LOGIC */


    /* Listeners */

    window.onresize = ()=> progressBarVolumeRef.style.visibility = "hidden";
    progressBarVolumeRef.firstElementChild.style.width = state.volume * 100 + "%";
    audio.addEventListener('loadedmetadata', ()=>{
        durationTimerRef.innerHTML = formatTime(audio.duration);
    });
    // Pist Listners
    progressBarRef.addEventListener("touchstart", dragStart, false);
    progressBarRef.addEventListener("touchend", dragEnd, false);
    progressBarRef.addEventListener("touchmove", drag, false);
    progressBarRef.addEventListener("mousedown", dragStart, false);
    progressBarRef.addEventListener("mouseup", dragEnd, false);
    progressBarRef.addEventListener("mousemove", drag, false);

    fullScreenVerrouRef.addEventListener("touchend", dragEnd, false);
    fullScreenVerrouRef.addEventListener("touchmove", drag, false);
    fullScreenVerrouRef.addEventListener("mousemove", drag, false);
    fullScreenVerrouRef.addEventListener("mouseup", dragEnd, false);
    //Volume Listeners
    progressBarVolumeRef.addEventListener("touchstart", dragStartVolume, false);
    progressBarVolumeRef.addEventListener("touchend", dragEndVolume, false);
    progressBarVolumeRef.addEventListener("touchmove", dragVolume, false);
    progressBarVolumeRef.addEventListener("mousedown", dragStartVolume, false);
    progressBarVolumeRef.addEventListener("mouseup", dragEndVolume, false);
    progressBarVolumeRef.addEventListener("mousemove", dragVolume, false);

    VolumeVerrouRef.addEventListener("touchend", dragEndVolume, false);
    VolumeVerrouRef.addEventListener("touchmove", dragVolume, false);
    VolumeVerrouRef.addEventListener("mousemove", dragVolume, false);
    VolumeVerrouRef.addEventListener("mouseup", dragEndVolume, false);
    // Volume Show-Hide
    volumeIconRef.addEventListener("mouseover", (e) => progressBarVolumeShow(e), false);
    progressBarVolumeRef.addEventListener("mouseover", (e) => progressBarVolumeShow(e), false);
    volumeIconRef.addEventListener("mouseout", progressBarVolumeHide, false);
    progressBarVolumeRef.addEventListener("mouseout", progressBarVolumeHide, false);

    /* Listeners */



    PlayPause.onclick = function(){
        switchPlay();
    };
    //Favorit

    Favorit.onclick = function(){
        data[state.idMusic[state.currentId]].favorit = !data[state.idMusic[state.currentId]].favorit;
        if(!data[state.idMusic[state.currentId]].favorit){
            Favorit.src = "../img/AudioControllerIcons/heart.png"
            $.ajax({
                url: 'music/favoris/delete/'+state.idMusic[state.currentId],
                type: 'DELETE',
                success: function (response) {
                    console.log('deleted from favoris');
                },
                error: function (response) {
                    console.log(response["fail"]);
                }
            });
        }else{
            Favorit.src = "../img/AudioControllerIcons/heart-full.png"
            $.ajax({
                url: 'music/favoris/add/'+state.idMusic[state.currentId],
                type: 'POST',
                success: function (response) {
                    console.log('add from favoris');
                },
                error: function (response) {
                    console.log(response["fail"]);
                }
            });
        }
    };
    DownloadMusic.onclick = function(){
        downloadMusic();
    };
    Previose.onclick = function(){
        previousAudio();
    };
    Next.onclick = function(){
        nextAudio();
    }
    Repeat.onclick = function(){
        repeat();

    };
    volumeIcon.onclick = function(){
        muteVolume();
    };
    currentTimeTimerRef.onclick = function () {
        formatTime(audio.currentTime);
    };
    durationTimerRef.onclick = function () {
        formatTime(audio.currentTime);
    };
    initMusic = (current = null)=>{
        if(current != null){
            console.log('in');
            state.currentId = current;
            console.log(state.currentId);
            audio.pause();

            PlayPause.firstElementChild.src = "../img/AudioControllerIcons/play.png";
            audio = new Audio(data[state.idMusic[state.currentId]].son);
            audio.preload = "metadata";
            audio.volume = state.volume;
            timeTrigger = null;
            fullProgressBarPurcent = 0;
            fullProgressBarPurcentVolume = 0;
            audio.addEventListener('loadedmetadata', ()=>{
                durationTimerRef.innerHTML = formatTime(audio.duration);
            });
            audio.addEventListener('ended', ()=>{
                stopTimeReading();
                currentTimeTimer.innerHTML = formatTime(0);
                progressBarRef.firstElementChild.style.width = "0";
                if (!state.repeated) {
                    nextAudio();
                }
                switchPlay();
            });
            downloadMusic(data[state.idMusic[state.currentId]].son);
            /* Variables globales */
            progressBar.firstElementChild.style.width = 0;
            currentTimeTimer.innerHTML = formatTime(0);
        }
        $('#m-'+state.idMusic[state.currentId]).addClass('selected');
        imgMusic.src = data[state.idMusic[state.currentId]].img;
        LoadingMusic.style.display = "none";
        TitleMusic.innerHTML = data[state.idMusic[state.currentId]].titre;
        Artist.innerHTML = data[state.idMusic[state.currentId]].artiste.nom +' ' + data[state.idMusic[state.currentId]].artiste.prenom;

        if(!data[state.idMusic[state.currentId]].favorit){
            Favorit.src = "../img/AudioControllerIcons/heart.png"
        } else {
            Favorit.src = "../img/AudioControllerIcons/heart-full.png"
        }
    }
    initMusic();
    //Creation des elements

    state.idMusic.forEach(function (idMusic) {
        console.log(idMusic);
        musicItem = $('<div class="musicItem" id="m-'+idMusic+'">'+
            '<img class="musicItemsImg" src="'+data[idMusic].img+'" alt="">'+
            '<div class="titlesItem">'+
            '<h2 class="titleMusic">'+data[idMusic].titre+'</h2>'+
            '<h2 class="artistMusic">'+data[idMusic].artiste.nom+' '+ data[idMusic].artiste.prenom+'</h2>'+
            '<h2 class="styleMusic">'+data[idMusic].style+'</h2>'+
            '</div>'+
            '<div class="racourciesItem">'+
            '<img src="../img/AudioControllerIcons/DownloadMusique.png" alt="">'+
            '<img src="../img/AudioControllerIcons/heart.png" alt="">'+
            '</div>'+
            '</div>'
        );
        $('#musicItems').append(musicItem);
        $('#m-'+idMusic).on('click', function (e) {
            $('#m-'+state.idMusic[state.currentId]).removeClass('selected');
            if(e.target.id == ""){
                if(e.target.parentNode.id == ""){
                    if(e.target.parentNode.parentNode.id != ""){
                        idMusicClicked = e.target.parentNode.parentNode.id.substring(2);
                        current = state.idMusic.indexOf(''+idMusicClicked);
                        initMusic(current);
                    }
                } else {
                    idMusicClicked = e.target.parentNode.id.substring(2);
                    current = state.idMusic.indexOf(''+idMusicClicked);
                    initMusic(current);
                }
            }else {
                idMusicClicked = e.target.id.substring(2);
                current = state.idMusic.indexOf(''+idMusicClicked);
                initMusic(current);
            }
        });
    });

    //Creation des elements
});