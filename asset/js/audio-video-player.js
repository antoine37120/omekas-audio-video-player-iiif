document.addEventListener('DOMContentLoaded', function () {
    var container = document.querySelector('.audio-player-container');
    var resourceId = container.getAttribute('data-resource-id');
    var player;
    var wavesurfer;
    var regionsPlugin;

    // UI Elements
    var annotationControls = document.getElementById('annotation-controls');
    var addRegionBtn = document.getElementById('add-region-btn');
    var annotationForm = document.getElementById('annotation-form');
    var saveAnnotationBtn = document.getElementById('save-annotation-btn');
    var deleteAnnotationBtn = document.getElementById('delete-annotation-btn');
    var cancelAnnotationBtn = document.getElementById('cancel-annotation-btn');
    var annotationList = document.getElementById('annotation-list');

    // Form Fields
    var inputId = document.getElementById('annotation-id');
    var inputTitle = document.getElementById('annotation-title');
    var inputDescription = document.getElementById('annotation-description');
    var inputStart = document.getElementById('annotation-start');
    var inputEnd = document.getElementById('annotation-end');

    if (!resourceId) {
        console.warn('No resource ID found, annotations disabled.');
        return;
    }

    // Show controls if resource ID is present
    annotationControls.style.display = 'block';

    /*var options = {
        controls: true,
        autoplay: false,
        preload: 'auto',
        fluid: true,
        plugins: {
            wavesurfer: {
                displayMilliseconds: true,
                debug: true,
                waveColor: 'grey',
                progressColor: 'black',
                cursorColor: 'black',
                hideScrollbar: true,
                plugins: []
            }
        }
    };*/

    var CremWaveSurfer = window.CremWaveSurfer
    /* eslint-disable */
    var options = {
        controls: true,
        autoplay: false,
        loop: false,
        muted: false,
        fluid: true,
        width: 600,
        height: 300,
        bigPlayButton: false,
        plugins: {
            wavesurfer: {
                backend: 'MediaElement',
                displayMilliseconds: false,
                debug: true,
                waveColor: 'grey',
                progressColor: 'black',
                cursorColor: 'black',
                hideScrollbar: true,
                plugins: [
                    // timeline
                    window.CremWaveSurfer.timeline.create({
                        container: '#wave-timeline'
                    }),
                    // regions
                    // regions
                    regionsPlugin = window.CremWaveSurfer.regions.create({
                        regions: [
                            {
                                start: 1.123,
                                end: 5,
                                content: 'Region 1',
                                color: 'rgba(255, 255, 205, 0.7)',
                                drag: false,
                                resize: false,
                            }, {
                                start: 6.5,
                                end: 90,
                                content: 'Region 2 df eg egegeg egeg egeg ege gegg ege e egegeg eeg egeg eg egeg eeg ',
                                color: 'rgba(205, 255, 255, 0.6)',
                                drag: false,
                                resize: false,
                            }
                        ]
                    })
                ]
            }
        }
    };

    // create player
    var player = CremVideojs('myAudio', options, function () {
        // print version information at startup
        var msg = 'Using video.js ' + CremVideojs.VERSION +
            ' with videojs-wavesurfer ' +
            CremVideojs.getPluginVersion('wavesurfer') +
            ' and wavesurfer.js ' + CremWaveSurfer.VERSION;
        CremVideojs.log(msg);
        var src = document.querySelector('#myAudio').getAttribute('src');
        var type = document.querySelector('#myAudio').getAttribute('type');
        // load file
        player.src({ src: src, type: type });
    });

    player.on('waveReady', function () {
        // print wavesurfer.js plugins
        CremVideojs.log('active wavesurfer.js plugins: ',
            player.wavesurfer().surfer.getActivePlugins());

        // listen for regions plugin events
        player.wavesurfer().surfer.on('region-click', function (region, e) {
            console.log("region click!");
        });
    });

    // error handling
    player.on('error', function (element, error) {
        console.warn('ERROR:', error);
    });

    /*
        player = videojs('my-video', options, function () {
            var msg = 'Using video.js ' + videojs.VERSION +
                ' with videojs-wavesurfer ' + videojs.getPluginVersion('wavesurfer') +
                ' and wavesurfer.js ' + WaveSurfer.VERSION;
            videojs.log(msg);
    
            wavesurfer = player.wavesurfer().surfer;
    
            // Initialize Regions Plugin
            if (WaveSurfer.Regions == false) {
                regionsPlugin = wavesurfer.registerPlugin(
                    WaveSurfer.Regions.create()
                );
    
                // Load annotations
                loadAnnotations();
    
                // Event Listeners
                regionsPlugin.on('region-clicked', function (region, e) {
                    e.stopPropagation(); // Prevent seeking
                    editRegion(region);
                });
    
                regionsPlugin.on('region-updated', function (region) {
                    if (inputId.value == region.id) {
                        inputStart.value = region.start.toFixed(2);
                        inputEnd.value = region.end.toFixed(2);
                    }
                });
            } else {
                console.error('WaveSurfer Regions plugin not found.');
            }
        });
    */
    var currentRegion;

    function saveAnnotation(data) {
        console.log('Saving annotation:', data);
        if (currentRegion) {
            currentRegion.remove();
            currentRegion = null;
        }

        // Add the saved region
        regionsPlugin.addRegion({
            id: data.id || Date.now().toString(),
            start: parseFloat(data.time),
            end: parseFloat(data.time_end),
            content: data.title,
            color: 'rgba(255, 255, 205, 0.7)',
            drag: false,
            resize: false
        });

        closeForm();
    }

    function deleteAnnotation(id) {
        console.log('Deleting annotation:', id);
        // Implement delete logic here (e.g., find region by id and remove)
        // For now, just close form
        closeForm();
    }

    function closeForm() {
        annotationForm.style.display = 'none';
        deleteAnnotationBtn.style.display = 'none';
        inputId.value = '';
        inputTitle.value = '';
        inputDescription.value = '';
        inputStart.value = '';
        inputEnd.value = '';
    }

    function loadAnnotations() {
        // Reload annotations if needed
        if (currentRegion) {
            currentRegion.remove();
            currentRegion = null;
        }
    }

    // --- Event Handlers ---

    addRegionBtn.onclick = function () {
        if (!regionsPlugin) {
            console.error('Regions plugin not initialized.');
            return;
        }
        var currentTime = player.currentTime();
        inputId.value = '';
        inputTitle.value = '';
        inputDescription.value = '';
        inputStart.value = currentTime.toFixed(2);
        inputEnd.value = (currentTime + 5).toFixed(2); // Default 5s duration

        deleteAnnotationBtn.style.display = 'none';
        annotationForm.style.display = 'block';

        // Create a temporary region for visual feedback
        currentRegion = regionsPlugin.addRegion({
            start: currentTime,
            end: currentTime + 5,
            color: 'rgba(0, 255, 0, 0.1)'
        });
    };

    saveAnnotationBtn.onclick = function () {
        var data = {
            id: inputId.value,
            title: inputTitle.value,
            description: inputDescription.value,
            time: inputStart.value,
            time_end: inputEnd.value
        };
        saveAnnotation(data);
    };

    deleteAnnotationBtn.onclick = function () {
        var id = inputId.value;
        if (id) {
            deleteAnnotation(id);
        }
    };

    cancelAnnotationBtn.onclick = function () {
        closeForm();
        loadAnnotations(); // Revert any temporary regions
    };
});
