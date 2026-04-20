import {DataTableFactory, registerDataTable, registerPage} from "@core/Registry";
import Page from '@core/Page';
import {compressImageAuto, selectFile, uploadFile} from "@core/Upload";
import Swal from "sweetalert2";
import htmx from "htmx.org";

let pageName = 'wx.material';

class MaterialPage extends Page {
  constructor() {
    super(pageName);
  }

  init() {
    this.bindEvents();
  }

  initDataTable() {
    const action = this.$('.table').attr('data-action');
    if (!action) return;
    this.table = DataTableFactory.create(this.$('.table'), {
      stateSave: true,
      searching: false,
      lengthChange: false,
      bInfo: false,
      pageLength: 20,
      ajax: action,
      rowId: 'media_id',
      columns: [
        {data: 0, render: this.formatData},
        {data: 1, render: this.formatData},
        {data: 2, render: this.formatData},
        {data: 3, render: this.formatData},
        {data: 4, render: this.formatData}
      ],
      drawCallback: (settings) => {
        this.initTooltips(settings.api.table().container());
      }
    });
    this.use(registerDataTable(this.$('.table[id]').attr('id'), this.table));
  }

  formatData(data) {
    if (data) {
      let image;
      if (data.type === 'image') image = data.url;
      if (data.type === 'video') image = data.cover_url;

      if (image) {
        return '<div class="card mb-0" data-bs-toggle="tooltip" data-bs-title="' + data.name + '">\n' +
          '  <div style="background-image:url(' + image + ')" class="card-img-top">\n' +
          '</div>';
      } else {
        return '<div class="card mb-0 p-2">' + data.name + '</div>';
      }
    }
  }

  destroy() {
  }

  bindEvents() {
    this.on('click', '.card-tools button', async (e) => {
      switch (e.currentTarget.dataset.type) {
        case 'image':
        case 'voice':
        case 'video':
          $(e.currentTarget).closest('.card-tools').find('btn-outline-success').removeClass('btn-outline-success').addClass('btn-outline-info');
          $(e.currentTarget).removeClass('btn-outline-info').addClass('btn-outline-success');

          const currentUrl = this.table.instance.ajax.url();
          if (!currentUrl.includes(e.currentTarget.dataset.type)) {
            const lastSlashIndex = currentUrl.indexOf('/list');
            this.table.instance.ajax.url(currentUrl.substring(0, lastSlashIndex + 5) + '/' + e.currentTarget.dataset.type).load();
          }
          break;
        case 'uploadImage':
          let file = await selectFile({accept: 'image/jpeg,image/png'});
          if (!file) return;

          // 上传图片小于1M
          if (file.size > 1048576) {
            if (file.type.startsWith('image/')) {
              file = await compressImageAuto(file,
                {
                  maxWidth: 1280,
                  maxHeight: 960,
                  quality: 0.85,
                  useWebP: false,
                  maxSizeMB: 1
                }
              );
            }
          }

          const result = await uploadFile(file, e.currentTarget.closest('.card-tools').dataset.uploadAction);
          if (result.message) {
            Swal.fire({icon: 'error', title: '上传失败', text: result.message}).then();
            return;
          }

          const fd = new FormData();
          fd.append('filePath', result.url);
          const res = await fetch(e.currentTarget.dataset.action, {method: 'POST', body: fd});
          const data = await res.json();

          if (data.url) {
            await Swal.fire({
              icon: 'success',
              title: '图片文件已成功添加到微信素材库',
              showConfirmButton: false,
              timer: 3000
            });
          } else {
            Swal.fire({icon: 'error', title: '上传失败', text: data.errmsg}).then();
          }

          break;
        case 'uploadVoice':
          const voice = await selectFile({accept: 'audio/mpeg'});
          if (!voice) return;

          // 上传语音文件小于2M
          if (voice.size > 2097152) {
            await Toast.fire({icon: 'error', title: '图片素材不能大于2M'});
            return;
          }

          const result_voice = await uploadFile(voice, e.currentTarget.closest('.card-tools').dataset.uploadAction);
          if (result_voice.message) {
            Swal.fire({icon: 'error', title: '上传失败', text: result_voice.message}).then();
            return;
          }

          const voice_fd = new FormData();
          voice_fd.append('filePath', result_voice.url);
          const res_voice = await fetch(e.currentTarget.dataset.action, {method: 'POST', body: voice_fd});
          const voice_data = await res_voice.json();

          if (voice_data.url) {
            await Swal.fire({
              icon: 'success',
              title: '音频文件已成功添加到微信素材库',
              showConfirmButton: false,
              timer: 3000
            });
          } else {
            Swal.fire({icon: 'error', title: '上传失败', text: voice_data.errmsg}).then();
          }
          break;

        case 'uploadVideo':
          const video = await selectFile({accept: 'video/mp4'});
          if (!video) return;

          // 上传语音文件小于10M
          if (video.size > 10485760) {
            await Toast.fire({icon: 'error', title: '图片素材不能大于10M'});
            return;
          }

          await Swal.fire('视频上传中...');
          Swal.showLoading();

          const result_video = await uploadFile(video, e.currentTarget.closest('.card-tools').dataset.uploadAction);
          if (result_video.message) {
            Swal.fire({icon: 'error', title: '上传失败', text: result_video.message}).then();
            return;
          }

          Swal.fire({
            title: '完善视频信息',
            html: '<input id="title" type="text" class="form-control" placeholder="视频素材的标题" autocomplete="off">' +
              '<textarea id="introduction" class="form-control" placeholder="视频素材的描述"></textarea>',
            showCancelButton: false,
            confirmButtonText: '确定',
            showLoaderOnConfirm: true,
            preConfirm: () => {
              const title = $('#title').val();
              const introduction = $('#introduction').val();
              if (!title || !introduction) {
                Swal.showValidationMessage('视频信息不能为空!');
              } else {
                return new Promise((resolve, reject) => {
                  $.post(e.currentTarget.dataset.action, {
                    filePath: result_video.url,
                    description: {title: title, introduction: introduction}
                  }, function (data) {
                    resolve(data)
                  }).fail(function (data) {
                    reject(data)
                  })
                }).catch(error => {
                  return error.responseJSON;
                });
              }
            },
            allowOutsideClick: false
          }).then((result) => {
            if (result.isConfirmed) {
              console.log(result);
              if (result.value.message) {
                Swal.fire({icon: 'error', title: result.value.message});
              } else {
                Swal.fire({
                  icon: 'success',
                  title: '素材成功添加到微信素材库',
                  showConfirmButton: false,
                  timer: 3000
                });
              }
            }
          });
          break;
      }

    });
    this.on('click', '.table .card', (e) => {
      const td = e.target.closest('td');
      const tr = e.target.closest('tr');
      if (!td || !tr) return;
      const data = this.table.instance.row(tr).data()[td.cellIndex];
      // 选择素材
      const modalElement = e.target.closest('.modal');
      const triggerEl = modalElement?._triggerEl;
      if (triggerEl) {
        // 寻找最近的父级容器（实例或页面）
        const parentEl = triggerEl.closest('[data-modal-instance], [data-page]');
        if (parentEl) {
          // 统一处理回传逻辑
          const logic = parentEl._customPageLogic || parentEl._customModalLogic;
          if (logic?.selMaterial) {
            logic.selMaterial(triggerEl, data);
          }
        }
        // 关闭 Modal
        const instance = window.bootstrap.Modal.getInstance(modalElement);
        document.activeElement?.blur();
        instance?.hide();
        return;
      }
      if (data.type === 'image') {// 图片
        Swal.fire({
          imageUrl: data.url,
          imageAlt: data.name,
          customClass: {
            image: 'm-0 img-fluid rounded shadow-lg'
          },
          width: 'auto',
          imageHeight: '500',
          padding: '0',
          input: "text",
          inputLabel: "请输入“删除”来确认删除图片素材",
          showCancelButton: true,
          confirmButtonColor: '#d33',
          confirmButtonText: '确定删除',
          cancelButtonText: '取消',
          inputValidator: (value) => {
            if (value !== '删除') {
              return "请输入“删除”来做最后确认!";
            }
          }
        }).then((result) => {
          if (result.isConfirmed) {
            // 删除素材
            htmx.ajax('delete', data.delete, {swap: 'none', target: e.target}).then(() => {
              this.table.reload();
            });
          }
        });
      } else if (data.type === 'video') {// 视频
        fetch(data.url)
          .then(r => r.json())
          .then(res => {
            if (!res.url) {
              Swal.fire('无法预览', '视频解析失败', 'error');
              return;
            }

            Swal.fire({
              title: data.name + '（流畅）',
              html: `<video src="${res.url}" controls autoplay playsinline style="width:100%;max-height:70vh;background:#000"></video>`,
              showCloseButton: true,
              input: "text",
              inputLabel: "请输入“删除”来确认删除视频素材",
              showCancelButton: true,
              confirmButtonColor: '#d33',
              confirmButtonText: '确定删除',
              cancelButtonText: '取消',
              inputValidator: (value) => {
                if (value !== '删除') {
                  return "请输入“删除”来做最后确认!";
                }
              }
            }).then((result) => {
              if (result.isConfirmed) {
                // 删除素材
                htmx.ajax('delete', data.delete, {swap: 'none', target: e.target}).then(() => {
                  this.table.reload();
                });
              }
            });
          });
      } else {// 音频
        Swal.fire({
          title: data.name,
          html: `<audio src="${data.url}" controls autoplay style="width:100%;"></audio>`,
          showCloseButton: true,
          input: "text",
          inputLabel: "请输入“删除”来确认删除音频素材",
          showCancelButton: true,
          confirmButtonColor: '#d33',
          confirmButtonText: '确定删除',
          cancelButtonText: '取消',
          inputValidator: (value) => {
            if (value !== '删除') {
              return "请输入“删除”来做最后确认!";
            }
          }
        }).then((result) => {
          if (result.isConfirmed) {
            // 删除素材
            htmx.ajax('delete', data.delete, {swap: 'none', target: e.target}).then(() => {
              this.table.reload();
            });
          }
        });
      }
    });
  }
}

registerPage(pageName, MaterialPage);
