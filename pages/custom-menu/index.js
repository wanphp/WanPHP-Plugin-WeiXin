import {registerPage} from "@core/Registry";
import Page from '@core/Page';

let pageName = 'wx.custom_menu';

class CustomMenuPage extends Page {
  constructor() {
    super(pageName);
  }

  init() {
    this.bindEvents();
  }

  destroy() {
  }

  bindEvents() {
  }
}

registerPage(pageName, CustomMenuPage);
