/* -----------------------
 *	Appplication
 * ----------------------- */

var App = function() {
	var that = {};

	that.init = function(data) { 
		//console.log(data);
		App.DraftKitModel.init(data);
		App.DraftKitView.init();
		App.Debug.init();
	};

	return that;
}();

/* -----------------------
 *	Debug
 * ----------------------- */
App.Debug = function() {
	var that = {},
		_dom = {};

	_dom.view = 'input#view_id';

	that.init = function() { 
		$(_dom.view).on('change', function(){
			 App.DraftKitModel.change({'view_id' : $(this).val()});
		});
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
		_viewID   = 0,
		_socket;

	that.init = function(data) {
		_socket = 'http://scotch/jaymar/draktkit/_sockets/socket.php';
		_data = data;
		_viewID = 0;
		that.setColumns();
	};

	that.change = function(params) {
		for(var p in params) {
			_handleChange(p, params[p]);
		}
	};

	function _handleChange(k, v) {
		//console.log(k, v);
		switch (k) {
			case 'view_id' : 
				_viewID = v;
				that.setColumns();
				_broadcast({action : k});
				break;
			case 'player_type' :
				// hitters, pitchers, all
				_getNewMaster(v);
				break;
		}
	}

	function _broadcast(p) {
		App.DraftKitView.update(p);
	}

	function _getNewMaster(player_type) {
		$.ajax({
			url     : _socket,
			data    : { action : 'get_master', player_type : player_type },
			success : function(rData) {
				_data.master =rData.master;
				_broadcast({action : 'player_type'});
			},
			error   : _onSocketError
		})
	}

	function _onSocketError(p) {

	} 

	that.getMaster = function(o) {
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

		// order the categories based on view col_keys arrangement 
		// build chunks of cols based on ordered categories
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

	_dom.content        = '#content',
	_dom.tableContainer = '#tableContainer';
	_dom.table          = 'table#data';
	_dom.views          = 'ul#views';
	_dom.rankCats       = 'select#rankCategories';
	_dom.playerType     = 'select#playerType'; 

	that.init = function() {
		_bindEvents();
		_upataTable();
	};

	that.update = function(p) {
		if (p.action == 'player')
		switch (p.action) {
			case 'player_type' :
				// remove loading state
				break
		}
		_upataTable();
	};

	function _bindEvents() {
		$(_dom.views).on('click', 'li', _onViewChage);
		$(_dom.rankCats).on('change', _onRankCatChange);
		$(_dom.playerType).on('change', _onPlayerTypeChange);
	}

	function _onViewChage() { 
		$(_dom.views + ' .selected').removeClass('selected');
		$(this).addClass('selected');

		var key = $(this).data('overall_rankings'),
			id  = $(this).data('id');
		if ($(this).data('view_key') == 'overall_rankings') {
			id = $(_dom.rankCats + ' option:selected').data('view_id');
		}

		// else if view key doesn have rank then disable _dom.rankCats
		_model.change({'view_id' : id });
	}

	function _onPlayerTypeChange(e) {
		_model.change({ 'player_type' : $(this).val() });
	}

	function _onRankCatChange(e) { console.log('a');
		_model.change({'view_id' : $(_dom.rankCats + ' option:selected').data('view_id')});
	}

	function _upataTable() {
		var columns = _model.getColumns();
		$(_dom.tableContainer).html(_getTable());


		  $table = $(_dom.table).DataTable( {
            "deferRender": true,
            "data":  _model.getMaster(),
            "columns": columns,
            "columnDefs"   : [ 
                { targets: 'noSort', orderable: false  },
                { targets: 'noSearch', searchable: false },
                { targets: 'dataOnly', visible: false },
                { targets: 'A', className: 'A'},
                { targets: 'B', className: 'B'}
            ],
            "paging": false,
            "searching": false,
            "info": false
        });
	}

	function _getTable(columnData) {
		var dataOnly     = ['17_p', 'pos', 'award_cand'],
			dataOnlyCols = ['E'];

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
          			var klass = (_.indexOf(dataOnly, o.category) > -1) ? ' dataOnly' : '';
          			if (klass == '' && (_.indexOf(dataOnlyCols, c.col_key) > -1)) klass = ' dataOnly';
          			html += '<th class="' + c.col_key + klass + '" data-id="' + c.id + '" data-colkey="' + c.col_key +'" data-category="' + c.category + '">' + c.label  + '(' + c.col_key + ')</th>'
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


