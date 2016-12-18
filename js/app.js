/* -----------------------
 *	Appplication
 * ----------------------- */

var App = function() {
	var that = {};

	that.init = function(data) { 
		//console.log(data);
		App.DraftKitModel.init(data);
		App.DraftKitView.init();
	};

	return that;
}();


/* -----------------------
 *	Model
 * ----------------------- */
 
App.DraftKitModel = function () {
	var that      = {},
		_data     = {},
		_columns  = [],
		_viewID   = 0;

	that.init = function(data) {
		_data = data;
		_viewID = 6;
		that.setColumns();
	};

	that.getMaster = function() {
		return _data.master;
	};

	that.getColumns = function() {
		var view    = that.getView(),	
			colkeys = view.col_keys.split(','),
			cols    =  _.filter(_data.config, function(o){ return (_.contains(colkeys, o.col_key)); });

		// keep columns sorted bases on view col_keys	
		_columns = [];
		_.each(colkeys, function(k){
			var o = _.findWhere(cols, { col_key: k });
			_columns.push({ data: '_' + o.col_key, label: o.label });
		});
		return _columns;
	};


	that.getCategories = function() {
		return _data.categories;
	};

	that.getColumnData = function() {
		var view     = that.getView(),	
			colkeys  = view.col_keys.split(','),
			data     = {},
			catOrder = 0;

		// order the categories base on view col_keys arrangement 
		// bulid chunks of cols based on ordered categories
		_.each(colkeys, function(key){
			var col = _.findWhere(_data.config, { col_key : key }),
				cat = col.category;
			if (data[cat] === undefined) {
				var label = _.findWhere(_data.categories, { category : cat}).label;
				data[cat] = { category : cat, ord : catOrder, label : label, columns : []};
				catOrder++;
			}
			data[cat]['columns'].push(col);
		});
		data =  _.sortBy(data, function(o){ return o.ord; }); 
		return data;
	};

	that.setColumns = function()  {
		var view    = that.getView(),	
			colkeys = view.col_keys.split(','),
			cols    =  _.filter(_data.config, function(o){ return (_.contains(colkeys, o.col_key)); });

		// keep columns sorted bases on view col_keys	
		_columns = [];
		_.each(colkeys, function(k){
			var o = _.findWhere(cols, { col_key: k });
			_columns.push({ data: o.col_key, label: o.label });
		});


	};

	that.setView = function(id) {
		_viewID = id || 0;
	};

	that.getView = function()  {
		if (_viewID == 0) {
			var col_keys = _.pluck(_data.config, 'col_key');
			return {id : 0, view_key : 'defualt', view_subkey : undefined, label : '', col_keys: col_keys.join() };
		} else {
			return _.findWhere(_data.views, {id: String(_viewID)});
		}
	};

	return that;
}();


/* -----------------------
 *	View
 * ----------------------- */

App.DraftKitView = function() {
	var that   = {},
		_model = App.DraftKitModel,
		_dom   = {},
		$table;

	_dom.content = '#content',
	_dom.table   = 'table#data';

	that.init = function() {
		_upataTable();
	};

	function _upataTable() {
		var columns = _model.getColumns();
		$(_dom.content).html(_getTable());


		  $table = $(_dom.table).DataTable( {
            "deferRender": true,
            "data":  _model.getMaster(),
            "columns": columns,
            "columnDefs"   : [ 
                { targets: 'noSort', orderable: false },
                { targets: 'noSearch', searchable: false}
            ]
        });
	}

	function _getTable(columnData) {
		var data = _model.getColumnData(),
			html  = '<table id="data" class="table table-striped table-bordered table-condensed">';
            html += '<thead>';
            html += '<tr class="categories">';
	        _.each(data, function(o){
	         	html += '<th data-category="' + o.category + '" colspan="' + o.columns.length + '">' + o.label + '</th>';
	        });
	        html += '</tr>';
          	html += '<tr class="columns">';
          	_.each(data, function(o){
          		_.each(o.columns, function(c){
          			html += '<th class="' + c.col_key + '" data-id="' + c.id + '" data-colkey="' + c.col_key +'" data-category="' + c.category + '">' + c.label + '</th>'
          		});
          	});
        	html += '</tr>'
          	html += '</thead>';
          	html + '<tbody></tbody>';
          	html += '</table>';
        return html;
	}


	return that;
}();


