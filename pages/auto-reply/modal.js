import {registerModal} from "@core/Registry";
import Page from '@core/Page';
import {compressImageAuto, selectFile, uploadFile} from "@core/Upload";

let modalName = 'wx.auto_reply.modal';

class Modal extends Page {
  constructor() {
    super(modalName);
  }

  init() {
    this.bindEvents();
  }

  destroy() {
  }

  selMaterial(target, data) {
    if (target.classList.contains('selMusicCover') && data.type !== 'image') {
      Toast.fire({icon: 'error', title: '请选择图片作为封面'}).then();
      return;
    }
    this.$('select[name="replyType"]').val(data.type);
    if (data.type === 'image') {// 图片
      if (target.classList.contains('selMaterial')) {
        if ($(target.parentNode).next()) $(target.parentNode).next().remove();
        // target.classList.remove('uploader_input_box');
        target.classList.add('w-100', 'h-auto');
        target.innerHTML = '<img src="' + data.url + '" alt="" class="w-100">' +
          '<input name="msgContent[Image][MediaId]" value="' + data.media_id + '" type="hidden">';
      }
      // 音乐封面
      if (target.classList.contains('selMusicCover')) {
        this.$('select[name="replyType"]').val('music');
        target.innerHTML = '<img src="' + data.url + '" class="object-fit-cover" style="width: 90px;height: 90px" alt="">' +
          '<input name="msgContent[Music][ThumbMediaId]" value="' + data.media_id + '" type="hidden">';
      }
    } else if (data.type === 'video') {// 视频
      const cover = data.cover_url;
      if (target.querySelector('img')) $(target.parentNode).next().remove();
      $(target).html('<img src="' + cover + '" class="object-fit-cover" style="width: 90px;height: 90px" alt="">' +
        '<input name="msgContent[Video][MediaId]" value="' + data.media_id + '" type="hidden">' +
        '<input name="msgContent[Cover]" value="' + cover + '" type="hidden">');
      $(target).parents('.input-group').append('<div style="flex:1 1 auto">\n' +
        '             <input name="msgContent[Video][Title]" class="form-control" placeholder="视频标题" required value="' + data.name + '" style="height: 30px; border-bottom:0;border-radius: 0">' +
        '             <textarea name="msgContent[Video][Description]" class="form-control" placeholder="视频描述" required style="height: 60px;border-radius: 0">' + data.description + '</textarea>' +
        '           </div>');
    } else {// 音频
      if ($(target.parentNode).next()) $(target.parentNode).next().remove();
      target.classList.add('w-100', 'h-auto');
      target.innerHTML = '<audio src="' + data.url + '" controls class="pe-5"><input name="msgContent[Voice][MediaId]" required value="' + data.media_id + '" type="hidden">';
    }
  }

  bindEvents() {
    this.on('change', 'select[name="msgType"]', (e) => {
      const value = e.target.value;
      const selectOption = e.currentTarget.options[e.currentTarget.selectedIndex];
      console.log(selectOption.dataset.action);
      const submitBtn = this.root.querySelector('[type="submit"]');
      if (submitBtn) {
        submitBtn.disabled = false;
      }
      this.$('#message').show();
      this.$('#keyword div').children().remove();
      let keyHtml = `<input id="key" name="key" type="text" class="form-control" required placeholder="关键词" autocomplete="off">`;
      if (['click', 'view'].includes(value)) {
        $.get(selectOption.dataset.action, (res) => {
          $('#keyword label').text('菜单点击事件');
          if (res.length > 0) {
            keyHtml = '<select id="key" name="key" class="form-control">';
            for (const re of res) {
              keyHtml += '<option value="' + re.key + '">' + re.name + '</option>'
            }
            keyHtml += '</select>';
          } else {
            keyHtml = '<div class="alert alert-danger">没有找到自定义菜单事件，请先到添加生成自定义菜单</div>';
            $('#message').hide();
            submitBtn.disabled = true;
          }
          this.$('#keyword div').html(keyHtml);
        });
        this.$('#keyword').show();
      } else if (value !== 'text' && value !== '') {
        keyHtml = '<input name="key" type="hidden" value="' + value + '" readonly>';
        this.$('#keyword div').html(keyHtml);
        this.$('#keyword').hide();
      } else {
        this.$('#keyword label').text('文本关键词');
        this.$('#keyword div').html(keyHtml);
        this.$('#keyword').show();
      }
    });
    this.on('change', 'select[name="replyType"]', (e) => {
      const value = e.target.value;
      let msgHtml = `<div class="form-group"><label for="msgContent.Content">回复文本</label>
          <textarea id="msgContent.Content" name="msgContent[Content]" class="form-control required" placeholder="回复文本,支持超链接"></textarea></div>`;
      switch (value) {
        case 'image':
        case 'voice':
        case 'video':
          msgHtml = '<div class="form-group">\n' +
            '              <label>回复图片/语音/视频</label>\n' +
            '              <div class="input-group">\n' +
            '                  <div class="input-group-append">\n' +
            '                    <div class="uploader_input_box selMaterial" title="请选择素材">\n' +
            '                      <i class="fas fa-plus" style="margin-top: 20px"></i>\n' +
            '                      <p>选择素材</p><input type="text" name="media_id" value="" required style="height: 1px;width: 1px;">\n' +
            '                    </div>\n' +
            '                  </div>\n' +
            '                </div>' +
            '            </div>';
          break;
        case 'music':
          msgHtml = '<div class="form-group">\n' +
            '       <label>回复音乐</label><div></div>\n' +
            '       <div class="input-group">\n' +
            '         <div class="input-group-append" style="margin-left: 0">\n' +
            '           <div class="uploader_input_box selMusicCover" style="border-bottom: 0;border-right: 0; margin-bottom: 0">\n' +
            '             <i class="fas fa-plus" style="margin-top: 20px"></i>\n' +
            '             <p>选择封面</p>\n' +
            '           </div>\n' +
            '         </div>' +
            '         <div style="flex:1 1 auto">' +
            '           <input name="msgContent[Music][Title]" class="form-control" placeholder="标题" required style="height: 30px;border-bottom:0;border-radius: 0">' +
            '           <textarea name="msgContent[Music][Description]" class="form-control" placeholder="描述" required style="height: 60px;border-bottom: 0;border-radius: 0"></textarea>' +
            '         </div>' +
            '       </div>' +
            '       <div class="input-group">\n' +
            '         <input type="text" name="msgContent[Music][MusicUrl]" required class="form-control" placeholder="音乐地址" style="border-radius: 0">\n' +
            '         <div class="input-group-prepend" style="margin-right: 0">\n' +
            '           <span class="input-group-text" id="uploadMusic">上传</span>\n' +
            '         </div>\n' +
            '       </div>' +
            '     </div>';
          break;
        case 'news':
          msgHtml = '<div class="form-group">\n' +
            '       <label>回复图文</label>' +
            '       <div class="articleItem"><input type="url" name="msgContent[Articles][0][Url]" class="form-control" placeholder="链接地址" required style="border-radius: 0">' +
            '         <div class="input-group">\n' +
            '           <div class="input-group-append" style="margin-left: 0">\n' +
            '             <div class="uploader_input_box" style="border-top: 0;border-right: 0">\n' +
            '               <i class="fas fa-plus" style="margin-top: 20px"></i>\n' +
            '               <p>选择封面</p>\n' +
            '             </div>\n' +
            '             <input type="hidden" name="msgContent[Articles][0][PicUrl]" value="" required>\n' +
            '           </div>' +
            '           <div style="flex:1 1 auto">\n' +
            '             <input name="msgContent[Articles][0][Title]" class="form-control" placeholder="标题" required style="height: 30px; border-top: 0;border-bottom:0;border-radius: 0">' +
            '             <textarea name="msgContent[Articles][0][Description]" class="form-control" placeholder="描述" required style="height: 60px;border-radius: 0"></textarea>' +
            '           </div>' +
            '         </div>' +
            '       </div>' +
            '     </div>';
          if (['click', 'view', 'subscribe'].includes(this.$('select[name="msgType"]').val())) {
            msgHtml += '<div class="uploader_input_box" id="addArticle" style="height: 30px;width: 100%;"><i class="fas fa-plus" style="margin-top: 7px"></i></div>';
          }
          break;
      }
      $('#form-message').html(msgHtml);
    });
    this.on('click', '.selMaterial', (e) => {
      const selectMaterial = this.root.querySelector('select[name="replyType"]').selectedOptions[0].dataset.selectMaterial;
      console.log(selectMaterial);
      window.openPageInModal(e.currentTarget, selectMaterial, 'wx.material', {title: '选择素材', size: 'xl'});
    });
    this.on('click', '.selMusicCover', (e) => {
      const selectMaterial = this.root.querySelector('select[name="replyType"] option[value="image"]').dataset.selectMaterial;
      window.openPageInModal(e.currentTarget, selectMaterial, 'wx.material', {title: '选择封面图片', size: 'xl'});
    });
    this.on('click', '#uploadMusic', async (e) => {
      const file = await selectFile({accept: 'audio/mpeg'});
      if (!file) return;

      const result = await uploadFile(file, e.currentTarget.closest('#form-message').dataset.action);
      if (result.message) {
        Swal.fire({icon: 'error', title: '上传失败', text: result.message}).then();
        return;
      }
      e.currentTarget.closest('.input-group').querySelector('input').value = result.url;
    });
    this.on('click', '#addArticle', (e) => {
      console.log(e);
      const articleLen = this.$('#form-message').find('.input-group').length;
      if (articleLen < 8) {
        $('#form-message').find('.form-group').append('<div class="articleItem">' +
          '<input type="url" name="msgContent[Articles][' + articleLen + '][Url]" required class="form-control" placeholder="链接地址" style="border-radius: 0">' +
          '               <div class="input-group">\n' +
          '                  <div class="input-group-append" style="margin-left: 0">\n' +
          '                    <div class="uploader_input_box" id="cropCover" style="border-top: 0;border-right: 0">\n' +
          '                      <i class="fas fa-plus" style="margin-top: 20px"></i>\n' +
          '                      <p>选择封面</p>\n' +
          '                    </div>\n' +
          '                    <input type="hidden" name="msgContent[Articles][' + articleLen + '][PicUrl]" required value="">\n' +
          '                  </div>' +
          '                  <div style="flex:1 1 auto">\n' +
          '                   <input name="msgContent[Articles][' + articleLen + '][Title]" class="form-control" placeholder="标题" required style="height: 30px; border-top: 0;border-bottom:0;border-radius: 0">' +
          '                   <textarea name="msgContent[Articles][' + articleLen + '][Description]" class="form-control" placeholder="描述" required style="height: 60px;border-radius: 0"></textarea>' +
          '                  </div>' +
          '                  <div class="input-group-append"><i class="fas fa-trash-alt input-group-text p-0" role="button" style="height: 90px;line-height:90px;border-radius: 0; border-top: 0"></i></div>' +
          '               </div>' +
          '             </div>');
      } else {
        $(this).hide();
      }
    });
    this.on('click', '#form-message .fa-trash-alt', (e) => {
      this.$(e.target).closest('.articleItem').remove();
      this.$('#form-message').find('#addArticle').show();
    });
    this.on('click', '#form-message .articleItem .uploader_input_box', async (e) => {
      const file = await selectFile({accept: 'image/jpeg,image/png'});
      if (!file) return;

      // 自动压缩图片
      const optimized = await compressImageAuto(file,
        {maxWidth: 200, maxHeight: 200, quality: 0.8}
      );
      const result = await uploadFile(optimized, e.currentTarget.closest('#form-message').dataset.action);
      if (result.message) {
        Swal.fire({icon: 'error', title: '上传失败', text: result.message}).then();
        return;
      }
      this.$(e.target).closest('.uploader_input_box').next('input').val(result.host + result.url);
      this.$(e.target).closest('.uploader_input_box').html('<img src="' + result.host + result.url + '" class="object-fit-cover" style="width: 90px;height: 90px" alt="">');
    });
  }
}

registerModal(modalName, Modal);
