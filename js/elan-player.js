jQuery(document).ready(function($){



if (typeof(imdi_elan_player_object) != 'undefined') {

   if (imdi_elan_player_object.media_type == "video") {
    console.log("VIDEO");

    //var player = videojs('video');

    window.elan_tier_cues = {};
    
    function genereate_elan_tier_stylesheet() {

        if (window.elan_tier_style) {
            document.head.removeChild(window.elan_tier_style);
        }

        window.elan_tier_style = (function() {
            // Create the <style> tag
            var style = document.createElement("style");

            // Add a media (and/or media query) here if you'd like!
            // style.setAttribute("media", "screen")
            // style.setAttribute("media", "only screen and (max-width : 1024px)")

            // WebKit hack :(
            style.appendChild(document.createTextNode(""));

            // Add the <style> element to the page
            document.head.appendChild(style);

            tier_checkboxes = $("div.imdi-elan-tiers > fieldset > label.tier");
            var colors = Please.make_color({colors_returned: tier_checkboxes.length});
            $(tier_checkboxes).each(function(i) {
                tier_id = $(this).children("input").attr("id");

                tierClass = tier_id.replace(/ /g,"_")
                style.sheet.insertRule("." + tierClass + " { color: " + colors[i] + "}", 0);
                style.sheet.insertRule("::cue(." + tierClass + ") { color: " + colors[i] + "}", 0);
                $(this).addClass(tierClass);
                
            });

            return style;
        })();
    }
    window.genereate_elan_tier_stylesheet = genereate_elan_tier_stylesheet;

    function show_tier(id) {
        var video = document.getElementsByTagName('video')[0];
        var track = video.textTracks[0];

        $.ajax({
            type: "GET",
            url: "/wp-admin/admin-ajax.php",
            data: {
                action: "IMDI_elanvtt",
                node: imdi_elan_player_object.eaf_id,
                tier: id
            },
            cache: false,
            dataType: "json",
            success: function(json) {

                var cues = Array();

                $(json).each(function(i) {
                    cue = new VTTCue(this.start/1000, this.end/1000, "<c."+id.replace(/ /g,"_")+ ">" + this.content);
                    track.addCue(cue);
                    cues.push(cue)
                });

                window.elan_tier_cues[id] = cues;
            }
        });
    }
    window.show_tier = show_tier;

    function hide_tier(id) {
        var video = document.getElementsByTagName('video')[0];
        track = video.textTracks[0];

        $(window.elan_tier_cues[id]).each(function() {
            track.removeCue(this);
        });
    }
    window.hide_tier = hide_tier;

    function gen_tier_colors() {
        
    }

     var video = document.getElementsByTagName('video')[0];
     video.addEventListener("loadedmetadata", function() { 
         genereate_elan_tier_stylesheet();
         var track = this.addTextTrack("captions", "English", "de");
         track.mode = "showing";
    //     show_tier(imdi_elan_player_object.tier);
     });

    $("div.imdi-elan-tiers > fieldset > label.tier > :checkbox").change(function() {
        if ($(this).is(":checked")) {
            console.log("add " + $(this).attr('id'));
            show_tier($(this).attr('id'));
        } else {
            console.log("remove " + $(this).attr('id'));
            hide_tier($(this).attr('id'));    
        }
    });

    $("div.imdi-elan-tiers span#change-colors a").click(function(e) {
        e.preventDefault();
        $(e).trigger("mouseleave");
        $(e).blur();
        genereate_elan_tier_stylesheet();
    })


    console.log(imdi_elan_player_object.eaf_url);
    // $.ajax({
    //         type: "GET",
    //         url: imdi_elan_player_object.eaf_url,
    //         cache: false,
    //         dataType: "xml",
    //         success: function(xml) {

    //             var timeslots = {};
    //             window.tier_overlays = {};
    //             window.alignable_annotations = {};

    //             $(xml).find('TIME_ORDER').children('TIME_SLOT').each(function(){
    //                 timeslots[$(this).attr("TIME_SLOT_ID")] = $(this).attr("TIME_VALUE");
    //             });

    //             var tiers = $(xml).find('TIER');
    //             var colors = Please.make_color({colors_returned: tiers.length});

    //             $(tiers).each(function(i, e) {
    //                 var tier_overlay = {
    //                     overlays: [],
    //                     color: colors[i]
    //                 };
    //                 var tier_id = $(this).attr("TIER_ID");
    //                 $('#tiers > fieldset')
    //                     .append('<label style="color: ' + colors[i] + '" for="' + tier_id + '"><input type="checkbox" id="' + tier_id + '" />' + tier_id + '</label>');

    //                 $(this).children('ANNOTATION').children('ALIGNABLE_ANNOTATION').each(function() {
    //                     var ref_start = $(this).attr("TIME_SLOT_REF1");
    //                     var ref_end = $(this).attr("TIME_SLOT_REF2");

    //                     var start = timeslots[ref_start]/1000;
    //                     var end = timeslots[ref_end]/1000;

    //                     var overlay = {
    //                         start: start,
    //                         end: end,
    //                         align: "bottom",
    //                         content: "<span style='color: " + colors[i] + "'>" + $(this).children("ANNOTATION_VALUE").text() + "</span>"
    //                     }
                        
    //                     window.alignable_annotations[$(this).attr("ANNOTATION_ID")] = {start: start, end: end};
    //                     tier_overlay.overlays.push(overlay);
    //                 });

    //                 $(this).children('ANNOTATION').children('REF_ANNOTATION').each(function() {
    //                     var annotation = window.alignable_annotations[$(this).attr("ANNOTATION_REF")];

    //                     tier_overlay.overlays.push({
    //                         start: annotation.start,
    //                         end: annotation.end,
    //                         align: "bottom",
    //                         content: "<span style='color: " + colors[i] + "'>" + $(this).children("ANNOTATION_VALUE").text() + "</span>"
    //                     });
    //                 });

    //                 window.tier_overlays[tier_id] = tier_overlay;

    //             });

    //             function applyOverlays() {
    //                 var show_overlays = [];  
    //                 $('#tiers > fieldset > label > input:checked').each(function() {
    //                     show_overlays = show_overlays.concat(window.tier_overlays[$(this).attr('id')].overlays);
    //                 });


    //                 player.overlay({
    //                     content: '<strong>Default overlay content</strong>',
    //                     overlays: show_overlays
    //                 });
    //             }
    //             applyOverlays();

                
    //             $('#tiers > fieldset > label').click(function() {
    //                 applyOverlays();
    //             });



                
    //         }
    //     });
    

     
   } else {
  
       var audio_file = '';
       var wavesurfer = Object.create(WaveSurfer);
       var elan = Object.create(WaveSurfer.ELAN);
       var timeline = Object.create(WaveSurfer.Timeline);

        wavesurfer.init({
            container: '#wave',
            waveColor: 'violet',
            progressColor: 'purple',
            backend: 'MediaElement',
            scrollParent: false
        });

         elan.init({
            url: imdi_elan_player_object.eaf_url,
            container: '#annotations',
            // tiers: {
            //     "W-IPA": true,
            //     "W-RGMe": true
            // }
        });

        timeline.init({
            wavesurfer: wavesurfer,
            container: "#wave-timeline"
        });

        var progressBar = $('#progress-bar');
        var initialWidth = $(progressBar).width();

        wavesurfer.on('loading', function (percent, xhr) {
            $(progressBar).width((90/100) * percent + '%');
            if (percent == 100) {
                $('#progress-bar').hide();
                $('#kickoff-overlay').hide();
                $('#zoomin').show();
            }
        });

        $('#kickoff-overlay').click(function() {    

               for (i = 0; i < imdi_elan_player_object.audio_files.length; i++) {

                    var testAudio = document.createElement('audio');

                    var canPlay = !!testAudio.canPlayType && "" != testAudio.canPlayType(imdi_elan_player_object.audio_files[i].type);

                    if (canPlay) {
                        // $('#progress-bar').toggle();
                        wavesurfer.load(imdi_elan_player_object.audio_files[i].url);
                        wavesurfer.play();

                        $('#kickoff-overlay').hide();
                        $('#pause').show();


                        break;
                    }
               }
        });

        $('#pause').hide();
        $('#play').hide();
        $('#zoomin').hide();
        $('#zoomout').hide();


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

        $('#zoomin').click(function(e) {
            e.preventDefault();
            wavesurfer.params['scrollParent'] = true;
            wavesurfer.drawBuffer();
            $(this).toggle();
            $('#zoomout').toggle();
        });
        $('#zoomout').click(function(e) {
            e.preventDefault();
            wavesurfer.params['scrollParent'] = false;
            wavesurfer.drawBuffer();
            $(this).toggle();
            $('#zoomin').toggle();
        });

    

        elan.on('ready', function () {
            var classList = elan.container.querySelector('table').classList;
            [ 'table', 'table-striped', 'table-hover' ].forEach(function (cl) {
                classList.add(cl);
            });

            var table = $('.wavesurfer-annotations').DataTable({
                "paging": false,
                "ordering": false,
                "searching": false,
                "scrollY": "300px",
                "info": false
            });

            var colvis = new $.fn.dataTable.ColVis(table, {
                "buttonText": "Show / Hide ELAN tiers"
            });
            $('div#annotations').prepend(colvis.button());
        });

        elan.on('select', function (start, end) {
            wavesurfer.backend.play(start, end);
        });

        wavesurfer.on('finish', function() {
            $('#pause').hide();
            $('#play').show();
        });

        var prevAnnotation, prevRow, region;
        var onProgress = function (time) {
            var annotation = elan.getRenderedAnnotation(time);

            if (prevAnnotation != annotation) {
                prevAnnotation = annotation;

                region && region.remove();
                region = null;

                if (annotation) {
                    // Highlight annotation table row
                    var row = elan.getAnnotationNode(annotation);
                    prevRow && prevRow.classList.remove('success');
                    prevRow = row;
                    row.classList.add('success');
                    var before = row.previousSibling;
                    if (before) {
                        //elan.container.scrollTop = before.offsetTop;
                        //$('table.wavesurfer-annotations').parent().offset(before.offsetTop);
                        $('.dataTables_scrollBody').animate({
                            scrollTop: before.offsetTop
                        }, 800)
                    }

                    // Region
                    region = wavesurfer.addRegion({
                        start: annotation.start,
                        end: annotation.end,
                        resize: false,
                        color: 'rgba(223, 240, 216, 0.7)'
                    });
                }
            }
        };

        wavesurfer.on('audioprocess', onProgress);

    }
    }
});