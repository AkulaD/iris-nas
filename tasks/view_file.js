document.addEventListener("DOMContentLoaded", function () {
    const video = document.getElementById("main-video");
    const audio = document.getElementById("main-audio");
    const videoSpeed = document.getElementById("video-speed");
    const audioSpeed = document.getElementById("audio-speed");

    if (video) {
        if (videoSpeed) {
            videoSpeed.addEventListener("change", function () {
                video.playbackRate = parseFloat(this.value);
            });
        }

        video.addEventListener("dblclick", function (e) {
            const rect = video.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            const width = rect.width;
            const height = rect.height;

            if (y > height - 60) return;

            if (x < width / 2) {
                video.currentTime = Math.max(0, video.currentTime - 10);
            } else {
                video.currentTime = Math.min(video.duration, video.currentTime + 10);
            }
        });

        let lastTouchTime = 0;
        video.addEventListener("touchstart", function (e) {
            const now = Date.now();
            const rect = video.getBoundingClientRect();
            const x = e.touches[0].clientX - rect.left;
            const y = e.touches[0].clientY - rect.top;
            const width = rect.width;
            const height = rect.height;

            if (y > height - 60) return;

            if (now - lastTouchTime < 300) {
                e.preventDefault();
                if (x < width / 2) {
                    video.currentTime = Math.max(0, video.currentTime - 10);
                } else {
                    video.currentTime = Math.min(video.duration, video.currentTime + 10);
                }
            }
            lastTouchTime = now;
        }, { passive: false });
    }

    if (audio) {
        if (audioSpeed) {
            audioSpeed.addEventListener("change", function () {
                audio.playbackRate = parseFloat(this.value);
            });
        }
    }

    if (video || audio) {
        window.addEventListener("keydown", function (e) {
            if (document.activeElement.tagName === "INPUT" || 
                document.activeElement.tagName === "SELECT" || 
                document.activeElement.tagName === "TEXTAREA") {
                return;
            }

            const activeMedia = video ? video : audio;

            if (e.key === "ArrowRight") {
                e.preventDefault();
                activeMedia.currentTime = Math.min(activeMedia.duration, activeMedia.currentTime + 10);
            } else if (e.key === "ArrowLeft") {
                e.preventDefault();
                activeMedia.currentTime = Math.max(0, activeMedia.currentTime - 10);
            } else if (e.key === " ") {
                e.preventDefault();
                if (activeMedia.paused) {
                    activeMedia.play();
                } else {
                    activeMedia.pause();
                }
            }
        });
    }
});