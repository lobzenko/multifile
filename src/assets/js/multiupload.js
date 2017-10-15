(function ($){

	$.fn.multiupload = function( options ){

		var isVideo = function(path)
		{
			var ext = path.split('.').pop();
			ext = ext.toLowerCase();

			var exts = ['AVI','MPG','MPEG','MP4','M2TS','MOV','3GP','MKV']
			if (exts.indexOf(ext.toUpperCase())!==-1)
				return true;

			return false;
		}

		var li_tmpl = function(file, fileName, index, id_media, data)
		{
			if (!data)
				data = JSON.parse('{"name":"","description":""}');

			var description = fileName.split('_').join(' ').split('.');
			description.pop();
			description.join('.');

			if (!id_media)
				id_media = 0;

			if (!file)
				file = '';

			preview = '';


			if (settings.showPreview)
			{
				if (isVideo(file))
					preview = '<td width="190" align="center"><video width="150" height="100" controls="controls"><source src="'+file+'"></video></td>';
				else
					preview = '<td width="190" align="center"><img class="thumbnail" src="'+file+'"/></td>';
			}

			if (settings.tpl==1)
				return '<div id="file'+index+'" class="form-group">\
					<div class="input-group">\
						<input type="text" class="form-control" name="'+settings.relationname+'['+settings.group+']['+index+'][name]" rel="name" value="'+fileName+'" placeholder="Заголовок" />\
						<input type="hidden" name="'+settings.relationname+'['+settings.group+']['+index+'][file_path]" value="'+file+'"/>\
						<input type="hidden" name="'+settings.relationname+'['+settings.group+']['+index+'][id_media]" value="'+id_media+'"/>\
						<input type="hidden" name="'+settings.relationname+'['+settings.group+']['+index+'][ord]" rel="ord" value="'+index+'"/>\
						<input type="hidden" name="'+settings.relationname+'['+settings.group+']['+index+'][grouptype]" rel="name" value="'+settings.group+'"/>\
						<span class="input-group-btn">\
							<button class="btn btn-default" type="button" onclick="$(this).parent().parent().parent().remove(); return false;">\
								<span class="glyphicon glyphicon-trash" aria-hidden="true"></span>\
							</button>\
						</span>\
					</div>\
					<textarea maxlength="255" class="form-control" name="'+settings.relationname+'['+settings.group+']['+index+'][description]" rel="description" placeholder="Описание">'+data.description+'</textarea>\
				</div>';
			else
				return '<li id="file'+index+'">\
							<img src="'+file+'"/>\
							<input type="hidden" name="'+settings.relationname+'['+settings.group+']['+index+'][file_path]" value="'+file+'"/>\
							<input type="hidden" name="'+settings.relationname+'['+settings.group+']['+index+'][id_media]" value="'+id_media+'"/>\
							<input type="hidden" name="'+settings.relationname+'['+settings.group+']['+index+'][ord]" rel="ord" value="'+index+'"/>\
							<input type="hidden" name="'+settings.relationname+'['+settings.group+']['+index+'][name]" rel="name" value="'+fileName+'"/>\
							<input type="hidden" name="'+settings.relationname+'['+settings.group+']['+index+'][grouptype]" rel="name" value="'+settings.group+'"/>\
						<a class="close btn btn-default" onclick="$(this).parent().remove(); return false;">&times;</a>\
					</li>';
		}

		var settings = $.extend({
			relationname: 'Media',
			group: 1,
			tpl: 1,
			single:false,
			records: [],
			allowedExtensions: [],
			showPreview:false
		}, options );

		// раставляет сортировочный индекс при перемещении
		var reOrdFiles = function(container,selector){
			container.find('input[rel="ord"]').each(function(i){
				var row = $(this).closest(selector);
				$(this).val(row.index());
			});
		}

		return this.each(function(){
			var element = this;

			var new_index = settings.records.length;

			var files_list = $('<div class="file-uploaded"></div>');
			if (settings.tpl==1)
			{
				files_list.append('<div class="file-uploaded-container sortable"></div');

				if (!settings.single)
				{
					var table = files_list.find('table');
					table.sortable({
						items: 'tr',
						stop: function(event, ui){
							reOrdFiles(table,'tr');
						}
					}).disableSelection();
				}
			}
			else
			{
				files_list.append('<ul class="file-uploaded-container sortable"></ul>');
				files_list.find('ul').sortable({
					items: 'li',
					stop: function(event, ui){
						reOrdFiles(files_list,'li');
					}
				}).disableSelection();
			}

			$(this).after(files_list);

			files_list = files_list.find('.file-uploaded-container');

			if (settings.records.length>0)
			{
				for (var i=0; i<settings.records.length; i++)
				{
					if (!settings.records[i].name)
						settings.records[i].name = settings.records[i].file_path;

					files_list.append($(li_tmpl(settings.records[i].file_path, settings.records[i].name, i, settings.records[i].id,settings.records[i])));
				}
			}

			var uploader = new qq.FileUploader({
				element: element,
				action: settings.url,
				name: 'images',
				allowedExtensions: settings.allowedExtensions,
				onSubmit: function(id, fileName){
				},
				onComplete: function(id, fileName, responseJSON)
				{
					if (typeof(responseJSON.error)=='string')
						return false;

					if (!settings.single)
					{
						files_list.append($(li_tmpl(responseJSON.filelink, fileName, new_index, 0)));
						new_index++;
					}
					else
					{
						files_list.html('');
						files_list.append($(li_tmpl(responseJSON.filelink, fileName, new_index, 0)));
					}
				},
			});

			var active_file = 0;


			/*var deleteSingleFile = function(){
				$(".qq-upload-drop-area span").html('');
				$("#fnewfile").val('');
				$("#singleField").remove();

				return false;
			}*/
		});
	};
}(jQuery));