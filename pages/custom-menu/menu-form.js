import {registerPage} from "@core/Registry";
import Page from '@core/Page';
import htmx from "htmx.org";

let pageName = 'wx.custom_menu.add';

class MenuFormPage extends Page {
  constructor() {
    super(pageName);
  }

  init() {
    this.bindEvents();
  }

  destroy() {
  }

  bindEvents() {
    this.on('change', 'form select[name="type"]', (e) => {
      if (e.target.value === 'miniprogram') this.$("form .miniprogram").show();
      else this.$("form .miniprogram").hide();
      if (e.target.value === 'view' || e.target.value === 'miniprogram') {
        this.$("form .url").show();
        this.$("form .key").hide();
      } else {
        this.$("form .url").hide();
        this.$("form .key").show();
      }
    });
    this.on('click', 'form .btn-outline-danger', (e) => {
      const action = e.target.dataset.action;
      confirmDialog('是否确认删除此菜单', () => {
        htmx.ajax('delete', action, {
          swap: 'none',
          target: e.target,
          headers: {
            'HX-Trigger': 'refresh-page'
          }
        }).then();
      });
    });
  }
}

registerPage(pageName, MenuFormPage);
