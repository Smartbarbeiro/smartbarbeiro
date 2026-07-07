<script setup>
import { onMounted, onUnmounted, ref } from 'vue';

const emit = defineEmits(['complete']);

const canvasRef = ref(null);

let animationFrame = null;
let timeoutId = null;
let resizeHandler = null;
let particles = [];
let rockets = [];

const durationMs = 3800;
const colors = ['#ff4d6d', '#ffd166', '#06d6a0', '#4cc9f0', '#f72585', '#ffffff'];

const random = (min, max) => Math.random() * (max - min) + min;

const pickColor = () => colors[Math.floor(Math.random() * colors.length)];

class Rocket {
    constructor(canvas) {
        this.canvas = canvas;
        this.x = random(canvas.width * 0.15, canvas.width * 0.85);
        this.y = canvas.height;
        this.targetY = random(canvas.height * 0.15, canvas.height * 0.45);
        this.speed = random(7, 11);
        this.color = pickColor();
        this.trail = [];
    }

    update() {
        this.trail.push({ x: this.x, y: this.y, alpha: 1 });
        if (this.trail.length > 8) {
            this.trail.shift();
        }

        this.y -= this.speed;

        return this.y <= this.targetY;
    }

    draw(ctx) {
        this.trail.forEach((point, index) => {
            ctx.save();
            ctx.globalAlpha = (index + 1) / this.trail.length;
            ctx.fillStyle = this.color;
            ctx.beginPath();
            ctx.arc(point.x, point.y, 2, 0, Math.PI * 2);
            ctx.fill();
            ctx.restore();
        });

        ctx.save();
        ctx.fillStyle = this.color;
        ctx.beginPath();
        ctx.arc(this.x, this.y, 3, 0, Math.PI * 2);
        ctx.fill();
        ctx.restore();
    }
}

class Particle {
    constructor(x, y, color) {
        const angle = random(0, Math.PI * 2);
        const speed = random(1.5, 6.5);

        this.x = x;
        this.y = y;
        this.color = color;
        this.velocityX = Math.cos(angle) * speed;
        this.velocityY = Math.sin(angle) * speed;
        this.alpha = 1;
        this.decay = random(0.012, 0.022);
        this.gravity = 0.08;
    }

    update() {
        this.velocityX *= 0.985;
        this.velocityY += this.gravity;
        this.x += this.velocityX;
        this.y += this.velocityY;
        this.alpha -= this.decay;
    }

    draw(ctx) {
        if (this.alpha <= 0) {
            return;
        }

        ctx.save();
        ctx.globalAlpha = Math.max(this.alpha, 0);
        ctx.fillStyle = this.color;
        ctx.beginPath();
        ctx.arc(this.x, this.y, 2.2, 0, Math.PI * 2);
        ctx.fill();
        ctx.restore();
    }
}

const explode = (x, y, color) => {
    const count = Math.floor(random(36, 64));

    for (let index = 0; index < count; index += 1) {
        particles.push(new Particle(x, y, color));
    }

    for (let index = 0; index < 12; index += 1) {
        particles.push(new Particle(x, y, '#ffffff'));
    }
};

const resizeCanvas = (canvas) => {
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
};

const launchRocket = (canvas) => {
    rockets.push(new Rocket(canvas));
};

const animate = (canvas, ctx, startTime) => {
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    if (Math.random() < 0.08 && Date.now() - startTime < durationMs - 800) {
        launchRocket(canvas);
    }

    rockets = rockets.filter((rocket) => {
        const shouldExplode = rocket.update();
        rocket.draw(ctx);

        if (shouldExplode) {
            explode(rocket.x, rocket.y, rocket.color);
            return false;
        }

        return true;
    });

    particles = particles.filter((particle) => {
        particle.update();
        particle.draw(ctx);
        return particle.alpha > 0;
    });

    if (Date.now() - startTime < durationMs) {
        animationFrame = requestAnimationFrame(() => animate(canvas, ctx, startTime));
    }
};

onMounted(() => {
    const canvas = canvasRef.value;

    if (!canvas) {
        emit('complete');
        return;
    }

    const ctx = canvas.getContext('2d');
    const startTime = Date.now();

    resizeCanvas(canvas);

    resizeHandler = () => resizeCanvas(canvas);
    window.addEventListener('resize', resizeHandler);

    for (let index = 0; index < 4; index += 1) {
        setTimeout(() => launchRocket(canvas), index * 180);
    }

    animate(canvas, ctx, startTime);

    timeoutId = window.setTimeout(() => {
        emit('complete');
    }, durationMs + 200);
});

onUnmounted(() => {
    if (resizeHandler) {
        window.removeEventListener('resize', resizeHandler);
    }

    if (animationFrame) {
        cancelAnimationFrame(animationFrame);
    }

    if (timeoutId) {
        clearTimeout(timeoutId);
    }
});
</script>

<template>
    <div class="registration-fireworks-overlay" aria-hidden="true">
        <canvas ref="canvasRef" class="registration-fireworks-overlay__canvas" />
        <p class="registration-fireworks-overlay__message">
            Cadastro realizado!
        </p>
    </div>
</template>
