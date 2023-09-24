<?php
// Load the environment variables from the .ini file
$env = parse_ini_file('env.ini');

// Get the value of the YT_API_KEY variable
$api_key = $env['YT_API_KEY'];
?>
<!DOCTYPE html>
<html>
<head>
	<title>YouTube API Example</title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
	<style>
		.recent-video {
			display: flex;
			align-items: center;
			margin-bottom: 10px;
			cursor: pointer;
		}
		.recent-video img {
			margin-right: 10px;
		}
	</style>
</head>
<body>
	<div class="container mt-5">
		<div class="row">
			<div class="col-md-8">
				<div class="ratio ratio-16x9">
					<div id="player"></div>
				</div>
				<select id="speed" class="form-select mt-3">
					<option value="0.1">10%</option>
					<option value="0.2">20%</option>
					<option value="0.3">30%</option>
					<option value="0.4">40%</option>
					<option value="0.5">50%</option>
					<option value="0.6">60%</option>
					<option value="0.7">70%</option>
					<option value="0.8">80%</option>
					<option value="0.9">90%</option>
					<option value="1" selected>100%</option>
					<option value="1.1">110%</option>
					<option value="1.2">120%</option>
					<option value="1.3">130%</option>
					<option value="1.4">140%</option>
					<option value="1.5">150%</option>
				</select>
			</div>
			<div class="col-md-4">
				<label for="url" class="form-label">YouTube URL:</label>
				<div class="input-group mb-3">
					<input type="text" id="url" name="url" class="form-control" value="" onclick="this.select()" onkeydown="if (event.keyCode == 13) loadVideo()">
					<button class="btn btn-primary" type="button" onclick="loadVideo()">Load Video</button>
				</div>
				<div>
					Recent Videos:
					<div id="recent-videos"></div>
				</div>
			</div>
		</div>
	</div>
	<script src="https://www.youtube.com/iframe_api"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
	<script>
		var player;
		var recentVideos = [];
		var apiKey = '<?php echo $api_key; ?>';
		function onYouTubeIframeAPIReady() {
			player = new YT.Player('player', {
				playerVars: {
					'controls': 1,
					'autoplay': 0,
					'enablejsapi': 1
				},
				events: {
					'onReady': onPlayerReady
				}
			});
		}
		function onPlayerReady(event) {
			document.getElementById('speed').addEventListener('change', function() {
				var speed = parseFloat(this.value);
				player.setPlaybackRate(speed);
			});
			document.addEventListener('keydown', function(event) {
				if (event.keyCode == 32) {
					event.preventDefault();
					if (player.getPlayerState() == YT.PlayerState.PLAYING) {
						player.pauseVideo();
					} else {
						player.playVideo();
					}
				}
			});
			loadRecentVideos();
			loadLastVideo();
		}
		function loadVideo() {
			var url = document.getElementById('url').value;
			var videoId = getVideoIdFromUrl(url);
			if (videoId) {
				player.loadVideoById(videoId);
				addRecentVideo(url, videoId);
				saveRecentVideos();
				getVideoTitle(videoId, function(title) {
					document.title = title;
				});
			} else {
				alert('Invalid YouTube URL');
			}
		}
		function loadLastVideo() {
			var lastUrl = localStorage.getItem('lastUrl');
			if (lastUrl) {
				document.getElementById('url').value = lastUrl;
				loadVideo();
			}
		}
		function addRecentVideo(url, videoId) {
			var existingVideoIndex = -1;
			for (var i = 0; i < recentVideos.length; i++) {
				if (recentVideos[i].videoId == videoId) {
					existingVideoIndex = i;
					break;
				}
			}
			if (existingVideoIndex >= 0) {
				recentVideos.splice(existingVideoIndex, 1);
			}
			getVideoTitle(videoId, function(title) {
				recentVideos.unshift({url: url, videoId: videoId, title: title});
				if (recentVideos.length > 10) {
					recentVideos.pop();
				}
				updateRecentVideos();
				saveRecentVideos();
			});
		}
		function updateRecentVideos() {
			var container = document.getElementById('recent-videos');
			container.innerHTML = '';
			for (var i = 0; i < recentVideos.length; i++) {
				var item = document.createElement('div');
				item.className = 'recent-video';
				var thumbnail = document.createElement('img');
				thumbnail.src = 'https://img.youtube.com/vi/' + recentVideos[i].videoId + '/default.jpg';
				thumbnail.width = 120;
				thumbnail.height = 90;
				item.appendChild(thumbnail);
				var link = document.createElement('a');
				link.href = '#';
				link.innerHTML = recentVideos[i].title;
				link.addEventListener('click', createLoadVideoHandler(recentVideos[i].url));
				item.appendChild(link);
				container.appendChild(item);
			}
		}
		function createLoadVideoHandler(url) {
			return function(event) {
				event.preventDefault();
				document.getElementById('url').value = url;
				loadVideo();
			}
		}
		function loadRecentVideos() {
			var recentVideosJson = localStorage.getItem('recentVideos');
			if (recentVideosJson) {
				recentVideos = JSON.parse(recentVideosJson);
				updateRecentVideos();
			}
		}
		function saveRecentVideos() {
			localStorage.setItem('recentVideos', JSON.stringify(recentVideos));
			if (recentVideos.length > 0) {
				localStorage.setItem('lastUrl', recentVideos[0].url);
			}
		}
		function getVideoIdFromUrl(url) {
			var match = url.match(/[?&]v=([^&]+)/);
			if (match) {
				return match[1];
			} else {
				return null;
			}
		}
		function getVideoTitle(videoId, callback) {
			var request = new XMLHttpRequest();
			request.open('GET', 'https://www.googleapis.com/youtube/v3/videos?part=snippet&id=' + videoId + '&key=' + apiKey, true);
			request.onload = function() {
				if (request.status >= 200 && request.status < 400) {
					var response = JSON.parse(request.responseText);
					var title = response.items[0].snippet.title;
					callback(title);
				}
			};
			request.onerror = function() {
				console.error('Error loading video title');
			};
			request.send();
		}
	</script>
</body>
</html>
