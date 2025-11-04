// Virtual Loy Krathong - Interactive JavaScript
class VirtualLoyKrathong {
  constructor() {
    this.currentKrathong = {
      base: 'lotus',
      flower: 'jasmine',
      candle: 'traditional',
      incense: 'sand'
    };
    this.wishText = '';
    this.isMusicOn = true;
    this.floatingKrathongs = [];
    this.krathongCounter = 0;
    this.aiSuggestions = [
      "May your dreams flow as smoothly as the river beneath the moonlight.",
      "Wishing for peace, prosperity, and happiness to fill your life.",
      "May all your aspirations bloom like flowers in the sacred night.",
      "Let go of worries and watch them float away with your krathong.",
      "May the light of wisdom guide your path through life's journey.",
      "Sending love and gratitude to all beings in this moment.",
      "May your heart be as calm and serene as this peaceful river.",
      "Wishing for healing energy to flow through all that you do.",
      "May your troubles dissolve like incense smoke in the moonlight.",
      "Let your soul find peace in this sacred moment of floating."
    ];

    this.init();
  }

  init() {
    this.setupEventListeners();
    this.loadSavedData();
    this.updateKrathongPreview();
    this.startWaterAnimations();

    // Hide loading screen after a short delay
    setTimeout(() => {
      const loadingScreen = document.getElementById('loading-screen');
      if (loadingScreen) {
        loadingScreen.classList.add('fade-out');
        setTimeout(() => {
          loadingScreen.style.display = 'none';
        }, 1000);
      }
    }, 2000);
  }

  setupEventListeners() {
    // Component selection
    document.querySelectorAll('.option').forEach(option => {
      option.addEventListener('click', (e) => {
        this.selectComponent(e.currentTarget);
      });
    });

    // Wish text handling
    const wishText = document.getElementById('wish-text');
    const charCount = document.getElementById('char-count');

    if (wishText) {
      wishText.addEventListener('input', () => {
        this.wishText = wishText.value;
        if (charCount) {
          charCount.textContent = wishText.value.length;
        }
      });
    }

    // AI Suggest Wish
    const aiSuggestBtn = document.getElementById('ai-suggest-wish');
    if (aiSuggestBtn) {
      aiSuggestBtn.addEventListener('click', () => {
        this.suggestWish();
      });
    }

    // Float Krathong
    const floatBtn = document.getElementById('float-krathong');
    if (floatBtn) {
      floatBtn.addEventListener('click', () => {
        this.floatKrathong();
      });
    }

    // Clear All
    const clearBtn = document.getElementById('clear-all');
    if (clearBtn) {
      clearBtn.addEventListener('click', () => {
        this.clearAllKrathongs();
      });
    }

    // Share buttons
    document.getElementById('share-facebook')?.addEventListener('click', () => {
      this.shareOnFacebook();
    });

    document.getElementById('share-twitter')?.addEventListener('click', () => {
      this.shareOnTwitter();
    });

    document.getElementById('copy-link')?.addEventListener('click', () => {
      this.copyLink();
    });

    // Krathong preview interaction
    const currentKrathong = document.getElementById('current-krathong');
    if (currentKrathong) {
      currentKrathong.addEventListener('click', () => {
        this.showKrathongPreview();
      });
    }
  }

  selectComponent(element) {
    const component = element.dataset.component;
    const value = element.dataset.value;

    if (!component || !value) return;

    // Update current krathong
    this.currentKrathong[component] = value;

    // Update UI
    element.parentNode.querySelectorAll('.option').forEach(opt => {
      opt.classList.remove('selected');
    });
    element.classList.add('selected');

    // Update preview
    this.updateKrathongPreview();

    // Add visual feedback
    element.classList.add('success-flash');
    setTimeout(() => {
      element.classList.remove('success-flash');
    }, 600);
  }

  updateKrathongPreview() {
    const previewContainer = document.querySelector('.preview-krathong');
    if (!previewContainer) return;

    previewContainer.innerHTML = '';

    // Create krathong based on current selections
    const krathong = this.createKrathongElement(this.currentKrathong);
    previewContainer.appendChild(krathong);

    // Update current krathong display
    const currentKrathongDisplay = document.getElementById('current-krathong');
    if (currentKrathongDisplay) {
      currentKrathongDisplay.innerHTML = '';
      currentKrathongDisplay.appendChild(this.createKrathongElement(this.currentKrathong, true));
    }
  }

  createKrathongElement(components, isPreview = false) {
    const krathong = document.createElement('div');
    krathong.className = 'krathong' + (isPreview ? ' preview-krathong-element' : ' floating');
    krathong.style.width = isPreview ? '60px' : '40px';
    krathong.style.height = isPreview ? '60px' : '40px';

    // Base
    const base = document.createElement('div');
    base.className = `krathong-base ${components.base}-base`;
    krathong.appendChild(base);

    // Flowers
    const flower = document.createElement('div');
    flower.className = `krathong-flower ${components.flower}-flower`;
    krathong.appendChild(flower);

    // Candle
    const candle = document.createElement('div');
    candle.className = `krathong-candle ${components.candle}-candle`;
    krathong.appendChild(candle);

    // Flame
    const flame = document.createElement('div');
    flame.className = 'krathong-flame';
    krathong.appendChild(flame);

    // Incense
    for (let i = 0; i < 3; i++) {
      const incense = document.createElement('div');
      incense.className = 'krathong-incense';
      incense.style.left = `${25 + (i * 25)}%`;
      krathong.appendChild(incense);
    }

    return krathong;
  }

  suggestWish() {
    const randomIndex = Math.floor(Math.random() * this.aiSuggestions.length);
    const suggestedWish = this.aiSuggestions[randomIndex];
    const wishText = document.getElementById('wish-text');

    if (wishText) {
      wishText.value = suggestedWish;
      this.wishText = suggestedWish;

      // Update character count
      const charCount = document.getElementById('char-count');
      if (charCount) {
        charCount.textContent = suggestedWish.length;
      }

      // Visual feedback
      const aiButton = document.getElementById('ai-suggest-wish');
      if (aiButton) {
        const originalText = aiButton.innerHTML;
        aiButton.innerHTML = '<i class="fas fa-check"></i> Wish Added!';
        aiButton.classList.add('success-flash');

        setTimeout(() => {
          aiButton.innerHTML = originalText;
          aiButton.classList.remove('success-flash');
        }, 2000);
      }
    }
  }

  floatKrathong() {
    if (!this.wishText.trim()) {
      alert('Please write a wish before floating your krathong!');
      return;
    }

    // Create floating krathong
    const floatingKrathong = {
      id: Date.now(),
      components: {...this.currentKrathong},
      wish: this.wishText,
      timestamp: new Date(),
      position: {
        x: Math.random() * (window.innerWidth - 100),
        duration: Math.random() * 20 + 15
      }
    };

    // Add to floating krathongs
    this.floatingKrathongs.push(floatingKrathong);
    this.krathongCounter++;

    // Create visual krathong
    const krathongElement = this.createKrathongElement(floatingKrathong.components);
    krathongElement.id = `floating-krathong-${floatingKrathong.id}`;
    krathongElement.style.left = `${floatingKrathong.position.x}px`;
    krathongElement.style.animationDuration = `${floatingKrathong.position.duration}s`;

    // Add to river
    const riverContainer = document.getElementById('floating-krathongs');
    if (riverContainer) {
      riverContainer.appendChild(krathongElement);
    }

    // Animate floating
    this.animateFloatingKrathong(krathongElement, floatingKrathong);

    // Update counter
    this.updateKrathongCounter();

    // Save data
    this.saveData();

    // Show success message
    this.showFloatingMessage();

    // Clear wish for next krathong
    setTimeout(() => {
      document.getElementById('wish-text').value = '';
      this.wishText = '';
      document.getElementById('char-count').textContent = '0';
    }, 1000);
  }

  animateFloatingKrathong(element, krathongData) {
    // Simulate floating movement
    let startTime = Date.now();
    const duration = krathongData.position.duration * 1000;

    const animate = () => {
      const elapsed = Date.now() - startTime;
      const progress = elapsed / duration;

      if (progress >= 1) {
        // Remove krathong after animation completes
        if (element.parentNode) {
          element.parentNode.removeChild(element);
        }
        // Remove from array
        this.floatingKrathongs = this.floatingKrathongs.filter(k => k.id !== krathongData.id);
        return;
      }

      // Calculate position (simulate water current)
      const x = krathongData.position.x + (Math.sin(progress * Math.PI * 4) * 50);
      const y = Math.sin(progress * Math.PI * 2) * 10;

      element.style.transform = `translateX(${x - krathongData.position.x}px) translateY(${y}px)`;
      element.style.opacity = 1 - (progress * 0.3);

      requestAnimationFrame(animate);
    };

    animate();
  }

  updateKrathongCounter() {
    const counter = document.getElementById('total-count');
    if (counter) {
      counter.textContent = this.krathongCounter;

      // Add counter animation
      counter.style.transform = 'scale(1.2)';
      counter.style.color = '#FFD700';

      setTimeout(() => {
        counter.style.transform = 'scale(1)';
        counter.style.color = '';
      }, 500);
    }
  }

  showFloatingMessage() {
    // Create floating message
    const message = document.createElement('div');
    message.className = 'floating-message';
    message.innerHTML = `
            <i class="fas fa-heart"></i>
            <span>Your krathong has been released into the digital river!</span>
        `;

    message.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.9) 0%, rgba(255, 160, 0, 0.9) 100%);
            color: #1a1a3e;
            padding: 20px 30px;
            border-radius: 15px;
            font-weight: 600;
            z-index: 1000;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            display: flex;
            align-items: center;
            gap: 10px;
            animation: fadeInOut 3s ease-in-out;
        `;

    document.body.appendChild(message);

    setTimeout(() => {
      if (message.parentNode) {
        message.parentNode.removeChild(message);
      }
    }, 3000);
  }

  clearAllKrathongs() {
    if (confirm('Are you sure you want to clear all floating krathongs from the river?')) {
      this.floatingKrathongs = [];
      this.krathongCounter = 0;

      const riverContainer = document.getElementById('floating-krathongs');
      if (riverContainer) {
        riverContainer.innerHTML = '';
      }

      this.updateKrathongCounter();
      this.saveData();
    }
  }

  shareOnFacebook() {
    const url = encodeURIComponent(window.location.href);
    const text = encodeURIComponent('Check out my beautiful virtual Loy Krathong! 🌙✨');
    window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}&quote=${text}`, '_blank');
  }

  shareOnTwitter() {
    const url = encodeURIComponent(window.location.href);
    const text = encodeURIComponent('Check out my beautiful virtual Loy Krathong! 🌙✨ Floating dreams under the moonlight #LoyKrathong');
    window.open(`https://twitter.com/intent/tweet?url=${url}&text=${text}`, '_blank');
  }

  copyLink() {
    navigator.clipboard.writeText(window.location.href).then(() => {
      const button = document.getElementById('copy-link');
      const originalText = button.innerHTML;
      button.innerHTML = '<i class="fas fa-check"></i> Copied!';
      button.classList.add('success-flash');

      setTimeout(() => {
        button.innerHTML = originalText;
        button.classList.remove('success-flash');
      }, 2000);
    }).catch(() => {
      alert('Link copied: ' + window.location.href);
    });
  }

  showKrathongPreview() {
    // Show detailed preview modal
    const modal = document.createElement('div');
    modal.className = 'krathong-modal';
    modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h3><i class="fas fa-eye"></i> Your Krathong Details</h3>
                    <button class="modal-close"><i class="fas fa-times"></i></button>
                </div>
                <div class="modal-body">
                    <div class="krathong-showcase">
                        ${this.createKrathongElement(this.currentKrathong).outerHTML}
                    </div>
                    <div class="krathong-details">
                        <p><strong>Base:</strong> ${this.getComponentName('base', this.currentKrathong.base)}</p>
                        <p><strong>Flowers:</strong> ${this.getComponentName('flower', this.currentKrathong.flower)}</p>
                        <p><strong>Candles:</strong> ${this.getComponentName('candle', this.currentKrathong.candle)}</p>
                        <p><strong>Incense:</strong> ${this.getComponentName('incense', this.currentKrathong.incense)}</p>
                        ${this.wishText ? `<p><strong>Wish:</strong> "${this.wishText}"</p>` : ''}
                    </div>
                </div>
            </div>
        `;

    modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            animation: fadeIn 0.3s ease-in-out;
        `;

    const modalContent = modal.querySelector('.modal-content');
    modalContent.style.cssText = `
            background: linear-gradient(135deg, rgba(22, 33, 62, 0.95) 0%, rgba(15, 52, 96, 0.9) 100%);
            border: 1px solid rgba(255, 249, 230, 0.2);
            border-radius: 15px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            max-height: 80%;
            overflow-y: auto;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        `;

    const modalHeader = modal.querySelector('.modal-header');
    modalHeader.style.cssText = `
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            color: #FFD700;
        `;

    const closeBtn = modal.querySelector('.modal-close');
    closeBtn.style.cssText = `
            background: none;
            border: none;
            color: #E0E6ED;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 5px;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        `;

    closeBtn.addEventListener('click', () => {
      document.body.removeChild(modal);
    });

    modal.addEventListener('click', (e) => {
      if (e.target === modal) {
        document.body.removeChild(modal);
      }
    });

    document.body.appendChild(modal);
  }

  getComponentName(type, value) {
    const names = {
      base: {
        lotus: 'Lotus Leaf',
        banana: 'Banana Leaf',
        traditional: 'Traditional',
        modern: 'Modern Design'
      },
      flower: {
        jasmine: 'Jasmine',
        rose: 'Rose',
        marigold: 'Marigold',
        orchid: 'Orchid'
      },
      candle: {
        traditional: 'Traditional Candle',
        floating: 'Floating Candle',
        colored: 'Colored Candle'
      },
      incense: {
        sand: 'Sandalwood',
        'jasmine-incense': 'Jasmine',
        frankincense: 'Frankincense'
      }
    };
    return names[type]?.[value] || value;
  }

  startWaterAnimations() {
    // Add additional water movement effects
    const waterSurface = document.querySelector('.water-surface');
    if (waterSurface) {
      setInterval(() => {
        const randomWave = Math.random() * 0.1 + 0.95;
        waterSurface.style.transform = `scaleX(${randomWave})`;
      }, 3000);
    }
  }

  loadSavedData() {
    try {
      const saved = localStorage.getItem('virtualLoyKrathongData');
      if (saved) {
        const data = JSON.parse(saved);
        this.floatingKrathongs = data.floatingKrathongs || [];
        this.krathongCounter = data.krathongCounter || 0;

        // Recreate floating krathongs
        this.floatingKrathongs.forEach(krathongData => {
          const krathongElement = this.createKrathongElement(krathongData.components);
          krathongElement.id = `floating-krathong-${krathongData.id}`;
          krathongElement.style.left = `${krathongData.position.x}px`;
          krathongElement.style.animationDuration = `${krathongData.position.duration}s`;

          const riverContainer = document.getElementById('floating-krathongs');
          if (riverContainer) {
            riverContainer.appendChild(krathongElement);
          }
        });

        this.updateKrathongCounter();
      }
    } catch (e) {
      console.log('Could not load saved data:', e);
    }
  }

  saveData() {
    try {
      const data = {
        floatingKrathongs: this.floatingKrathongs,
        krathongCounter: this.krathongCounter,
        lastUpdated: new Date()
      };
      localStorage.setItem('virtualLoyKrathongData', JSON.stringify(data));
    } catch (e) {
      console.log('Could not save data:', e);
    }
  }
}

// Initialize the application when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
  new VirtualLoyKrathong();
});

// Handle window resize
window.addEventListener('resize', () => {
  // Recalculate positions for floating krathongs
  const krathongs = document.querySelectorAll('[id^="floating-krathong-"]');
  krathongs.forEach(krathong => {
    krathong.style.left = `${Math.random() * (window.innerWidth - 100)}px`;
  });
});

// Add some Easter eggs and interactions
document.addEventListener('mousemove', (e) => {
  // Subtle mouse following effect for stars
  const stars = document.querySelectorAll('.star');
  stars.forEach((star, index) => {
    const delay = index * 0.1;
    const x = (e.clientX / window.innerWidth) * 10 - 5;
    const y = (e.clientY / window.innerHeight) * 10 - 5;

    setTimeout(() => {
      star.style.transform = `translate(${x}px, ${y}px)`;
    }, delay * 100);
  });
});

// Keyboard shortcuts
document.addEventListener('keydown', (e) => {
  // Press 'F' to float krathong
  if (e.key.toLowerCase() === 'f') {
    const floatBtn = document.getElementById('float-krathong');
    if (floatBtn) floatBtn.click();
  }

  // Press 'Escape' to close modals
  if (e.key === 'Escape') {
    const modals = document.querySelectorAll('.krathong-modal');
    modals.forEach(modal => {
      if (modal.parentNode) {
        modal.parentNode.removeChild(modal);
      }
    });
  }
});

console.log('🌙 Virtual Loy Krathong - Welcome to the digital river of dreams! ✨');