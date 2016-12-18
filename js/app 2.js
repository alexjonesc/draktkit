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
		_viewID = 1;
		that.setColumns();
	};

	that.getColumns = function() {
		return _columns;
	};

	function _tester() {
		// var view      = that.getView(),	
		// 	colkeys   = view.col_keys.split(','); 
		// 	filtered  =  _.filter(_data.config, function(o){ return (_.contains(colkeys, o.col_key)); }),
		// 	cats      = _.map(filtered, function(o){ return _.findWhere(_data.categories, { category: o.category}); });
		// 	grouped   = {};
		
		// cats = _.uniq( _.collect(cats, function( cat){ return cat; }));
		// cats = _.sortBy(cats, function(o){ return o.ord; }); 
		// // _.each(cats, function(o){ o.cols = []; });
		// // _.each(colkeys, function(k){
		// // 	//console.log(k);
		// // 	var col = _.findWhere(_data.config, { col_key : k}),
		// // 		cat = _.findWhere(cats, { category : col.category });
		// // 	//cat.cols.push(col);
		// // 	//console.log(cat);
		// // });
		// console.log(cats);
		// _.each(filtered, function(o){
		// 	console.log(o.category);
		// });
		// _.each(cats, function(cat){
		// 	var results = _.groupBy(filtered, function(o){ return o.category == cat.category; });
		// 	//grouped[cat.category] = results['true'];
		// 	//console.log(cat.category);
		// });

		// //console.log(grouped);
	}

	that.getCategories = function() { _tester();
		// this is similar to 
		// select distinct(category) from 01_config where col_key IN('A','B','D','F','H','BM','BH','BI','BJ','S','T');
		var view    = that.getView(),	
			colkeys = view.col_keys.split(','); 
			cols    =  _.filter(_data.config, function(o){ return (_.contains(colkeys, o.col_key)); }),
			cats    =  _.map(cols, function(o){ return _.findWhere(_data.categories, { category: o.category}); });

		cats = _.uniq( _.collect(cats, function( cat){ return cat; }));

		// get counts of columns for each cat
		_.each(cats, function(c){
			var cnt = _.countBy(cols, function(o){ return (o.category == c.category) ? 'extists' : 'notExists'; });
			c.count = cnt['extists'];
		});
		cats = _.sortBy(cats, function(o){ return o.ord; }); 
		return cats;
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
		_dom   = {};

	_dom.content = '#content';

	that.init = function() {
		_setupTable();
	};

	function _setupTable() {
		var columns    = _model.getColumns(),
			categories = _model.getCategories();

		var tableHTML = _getTable(categories, columns);
		$(_dom.content).html(tableHTML);
	}

	function _getTable(categories, columns) { 
		var html  = '<table class="table table-striped table-bordered table-condensed">';
            html += '<thead>';
            html += '<tr class="categories">';
		_.each(categories, function (c){
			html += '<th data-category="' + c.category + '" colspan="' + c.count + '">' + c.label + '</th>';
		});
          html += '</tr>';
          html += '<tr class="columns">';
        _.each(columns, function (c){
        	html += '<th data-colkey="' + c.data +'">' + c.data + '</th>';
        });
          html += '</tr>'
          html += '</thead>';
          html + '<tbody></tbody>';
          html += '</table>';
        return html;
	}

	return that;
}();


