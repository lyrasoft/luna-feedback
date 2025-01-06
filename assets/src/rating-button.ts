import '@main';

class RatingButton {
  icon: HTMLElement | null;

  rated: boolean;
  type: string;
  id: string;

  constructor(protected el: HTMLElement) {
    this.icon = el.querySelector('i, span');
    const rated = el.dataset.rated;

    this.rated = rated === '1' || rated === 'true';
    this.type = el.dataset.type || '';
    this.id = el.dataset.id || '';

    this.el.addEventListener('click', async () => {
      await this.toggle();
      this.refreshStyle();
    });

    this.refreshStyle();
  }

  async toggle() {
    const config = u.data('rating');

    if (!config.isLogin) {
      location.href = config.loginUri;
      return;
    }

    const task = this.rated ? 'remove' : 'add';

    const ratedBak = this.rated;

    this.rated = !this.rated;
    this.el.dataset.rated = this.rated ? '1' : '0';

    try {
      const res = await u.$http.post(
        `@rating_ajax/${task}`,
        {
          targetId: this.id,
          type: this.type,
        }
      );

      this.el.dispatchEvent(
        new CustomEvent('rated', {
          detail: {
            rated: !this.rated,
            task,
            type: this.type,
            message: res.data.message,
          },
          bubbles: true
        })
      );
    } catch (e) {
      this.rated = ratedBak;
      this.el.dataset.rated = this.rated ? '1' : '0';

      console.error(e);
      if (e instanceof Error) {
        u.alert(e.message, '', 'warning');
      }
      throw e;
    }
  }

  refreshStyle() {
    if (this.el.dataset.classInactive || this.el.dataset.classActive) {
      this.el.classList.remove(
        ...this.classToList(this.el.dataset.classInactive || ''),
        ...this.classToList(this.el.dataset.classActive || '')
      );
    }

    if (this.rated) {
      this.icon?.setAttribute('class', this.el.dataset.iconActive || '');

      if (this.el.dataset.classActive) {
        this.el.classList.add(
          ...this.classToList(this.el.dataset.classActive)
        );
      }

      this.el.setAttribute('data-bs-original-title', this.el.dataset.titleActive || '');
    } else {
      this.icon?.setAttribute('class', this.el.dataset.iconInactive || '');

      if (this.el.dataset.classInactive) {
        this.el.classList.add(
          ...this.classToList(this.el.dataset.classInactive)
        );
      }

      this.el.setAttribute('data-bs-original-title', this.el.dataset.titleInactive || '');
    }

    setTimeout(() => {
      const tooltip = u.$ui.bootstrap.tooltip(this.el);
      tooltip.update();
    }, 50);
  }

  classToList(className: string): string[] {
    return className.split(' ').filter((t) => t !== '');
  }
}

u.directive(
  'rating-button',
  {
    mounted(el: HTMLElement) {
      setTimeout(() => {
        u.module(el, 'rating.button', () => new RatingButton(el));
      }, 0);
    }
  }
);
