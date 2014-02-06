jQuery(document).ready(function($){

var wavesurfer = Object.create(WaveSurfer);
var elan = Object.create(WaveSurfer.ELAN);

   elan.init({
        url: 'http://ike.rrz.uni-koeln.de:2095/proxy.php?csurl=http://lac.uni-koeln.de/corpora/demo/pewi/Annotations/elan-example1.eaf',
        container: '#annotations',
        tiers: {
            "W-IPA": true,
            "W-RGMe": true
        }
    });

wavesurfer.init({
    container: '#wave',
    waveColor: 'violet',
    progressColor: 'purple'
});

wavesurfer.load('http://ike.rrz.uni-koeln.de:2095/proxy.php?csurl=http://lac.uni-koeln.de/corpora/demo/pewi/Media/elan-example1.wav');

$('#play').click(function() {
	wavesurfer.play();
});

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