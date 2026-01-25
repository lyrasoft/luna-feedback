import { useUniDirective, module as module$1, data, useHttpClient, simpleAlert, useBs5Tooltip } from "@windwalker-io/unicorn-next";
class RatingButton {
  constructor(el) {
    this.el = el;
    this.icon = el.querySelector("i, span");
    const rated = el.dataset.rated;
    this.rated = rated === "1" || rated === "true";
    this.type = el.dataset.type || "";
    this.id = el.dataset.id || "";
    this.el.addEventListener("click", async () => {
      await this.toggle();
      this.refreshStyle();
    });
    this.refreshStyle();
  }
  icon;
  rated;
  type;
  id;
  async toggle() {
    const config = data("rating");
    if (!config.isLogin) {
      location.href = config.loginUri;
      return;
    }
    const task = this.rated ? "remove" : "add";
    const ratedBak = this.rated;
    this.rated = !this.rated;
    this.el.dataset.rated = this.rated ? "1" : "0";
    try {
      const { post } = await useHttpClient();
      const res = await post(
        `@rating_ajax/${task}`,
        {
          targetId: this.id,
          type: this.type
        }
      );
      this.el.dispatchEvent(
        new CustomEvent("rated", {
          detail: {
            rated: !this.rated,
            task,
            type: this.type,
            message: res.data.message
          },
          bubbles: true
        })
      );
    } catch (e) {
      this.rated = ratedBak;
      this.el.dataset.rated = this.rated ? "1" : "0";
      console.error(e);
      if (e instanceof Error) {
        simpleAlert(e.message, "", "warning");
      }
      throw e;
    }
  }
  refreshStyle() {
    if (this.el.dataset.classInactive || this.el.dataset.classActive) {
      this.el.classList.remove(
        ...this.classToList(this.el.dataset.classInactive || ""),
        ...this.classToList(this.el.dataset.classActive || "")
      );
    }
    if (this.rated) {
      this.icon?.setAttribute("class", this.el.dataset.iconActive || "");
      if (this.el.dataset.classActive) {
        this.el.classList.add(
          ...this.classToList(this.el.dataset.classActive)
        );
      }
      this.el.setAttribute("data-bs-original-title", this.el.dataset.titleActive || "");
    } else {
      this.icon?.setAttribute("class", this.el.dataset.iconInactive || "");
      if (this.el.dataset.classInactive) {
        this.el.classList.add(
          ...this.classToList(this.el.dataset.classInactive)
        );
      }
      this.el.setAttribute("data-bs-original-title", this.el.dataset.titleInactive || "");
    }
    setTimeout(async () => {
      const [tooltip] = await useBs5Tooltip(this.el);
      tooltip.update();
    }, 50);
  }
  classToList(className) {
    return className.split(" ").filter((t) => t !== "");
  }
}
const ready = useUniDirective(
  "rating-button",
  {
    mounted(el) {
      setTimeout(() => {
        module$1(el, "rating.button", () => new RatingButton(el));
      }, 0);
    }
  }
);
export {
  RatingButton,
  ready
};
//# sourceMappingURL=rating-button.js.map
