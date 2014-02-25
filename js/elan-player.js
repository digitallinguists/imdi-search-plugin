jQuery(document).ready(function($){

var wavesurfer = Object.create(WaveSurfer);
var elan = Object.create(WaveSurfer.ELAN);

if (typeof(imdi_elan_player_object) != 'undefined') {


  
   var audio_file = '';

    wavesurfer.init({
        container: '#wave',
        waveColor: 'violet',
        progressColor: 'purple'
    });

    var progressBar = $('#progress-bar');
    var initialWidth = $(progressBar).width();

    wavesurfer.on('loading', function (percent, xhr) {
        $(progressBar).width((90/100) * percent + '%');
    });

    $('#kickoff-overlay').click(function() {    

           for (i = 0; i < imdi_elan_player_object.audio_files.length; i++) {

                var testAudio = document.createElement('audio');

                var canPlay = !!testAudio.canPlayType && "" != testAudio.canPlayType(imdi_elan_player_object.audio_files[i].type);

                if (canPlay) {
                    // $('#progress-bar').toggle();
                    wavesurfer.load(imdi_elan_player_object.audio_files[i].url);
                    break;
                }
           }
    });

     wavesurfer.on('ready', function () {
        
        $('#kickoff-overlay').toggle();
        $('#progress-bar').toggle();

        var timeline = Object.create(WaveSurfer.Timeline);

        timeline.init({
            wavesurfer: wavesurfer,
            container: "#wave-timeline"
        });

           elan.init({
        url: imdi_elan_player_object.eaf_url,
        container: '#annotations',
        // tiers: {
        //     "W-IPA": true,
        //     "W-RGMe": true
        // }
    });
    });

    $('#pause').toggle();

    $('#play').click(function(e) {
        e.preventDefault();

    	wavesurfer.play();
        $(this).toggle();
        $('#pause').toggle();
    });
    $('#pause').click(function(e) {
        e.preventDefault();
        wavesurfer.pause();
        $(this).toggle();
        $('#play').toggle();
    });
}

    elan.on('ready', function () {
        var classList = elan.container.querySelector('table').classList;
        [ 'table', 'table-striped', 'table-hover' ].forEach(function (cl) {
            classList.add(cl);
        });
    });

     var prevAnnotation, prevRow;
    var onProgress = function () {
        var time = wavesurfer.backend.getCurrentTime();
        var annotation = elan.getRenderedAnnotation(time);

        if (prevAnnotation != annotation) {
            prevAnnotation = annotation;

            if (annotation) {
                // Highlight annotation table row
                var row = elan.getAnnotationNode(annotation);
                prevRow && prevRow.classList.remove('success');
                prevRow = row;
                row.classList.add('success');
                var before = row.previousSibling;
                if (before) {
                    elan.container.scrollTop = before.offsetTop;
                }

                var start = annotation.start;
                var end = annotation.end;
            } else {
                start = -100;
                end = -100;
            }

            // Markers
            wavesurfer.mark({
                id: 'start',
                position: start,
                color: 'green'
            });
            wavesurfer.mark({
                id: 'end',
                position: end,
                color: 'red'
            });

        }
    };

    wavesurfer.on('progress', onProgress);

});