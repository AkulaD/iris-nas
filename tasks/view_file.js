document.addEventListener("DOMContentLoaded", function () {
    const video = document.getElementById("main-video");
    const audio = document.getElementById("main-audio");
    const videoSpeed = document.getElementById("video-speed");
    const audioSpeed = document.getElementById("audio-speed");

    if (video) {
        videoSpeed.addEventListener("change", function () {
            video.playbackRate = parseFloat(this.value);
        });

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

        window.addEventListener("keydown", function (e) {
            if (document.activeElement.tagName === "INPUT" || document.activeElement.tagName === "SELECT" || document.activeElement.tagName === "TEXTAREA") return;

            if (e.key === "ArrowRight") {
                e.preventDefault();
                video.currentTime = Math.min(video.duration, video.currentTime + 10);
            } else if (e.key === "ArrowLeft") {
                e.preventDefault();
                video.currentTime = Math.max(0, video.currentTime - 10);
            } else if (e.key === " ") {
                e.preventDefault();
                if (video.paused) {
                    video.play();
                } else {
                    video.pause();
                }
            }
        });
    }

    if (audio) {
        audioSpeed.addEventListener("change", function () {
            audio.playbackRate = parseFloat(this.value);
        });

        window.addEventListener("keydown", function (e) {
            if (document.activeElement.tagName === "INPUT" || document.activeElement.tagName === "SELECT" || document.activeElement.tagName === "TEXTAREA") return;

            if (e.key === "ArrowRight") {
                e.preventDefault();
                audio.currentTime = Math.min(audio.duration, audio.currentTime + 10);
            } else if (e.key === "ArrowLeft") {
                e.preventDefault();
                audio.currentTime = Math.max(0, audio.currentTime - 10);
            } else if (e.key === " ") {
                e.preventDefault();
                if (audio.paused) {
                    audio.play();
                } else {
                    audio.pause();
                }
            }
        });
    }
});