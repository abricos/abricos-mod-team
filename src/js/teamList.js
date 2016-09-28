var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: '{C#MODNAME}', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    var TeamListRowWidgetExt = function(){
    };
    TeamListRowWidgetExt.ATTRS = {
        team: {value: null}
    };
    TeamListRowWidgetExt.prototype = {
        buildTData: function(){
            return this.get('team').toJSON(true);
        },
    };
    NS.TeamListRowWidgetExt = TeamListRowWidgetExt;

    NS.TeamListWidget = Y.Base.create('teamListWidget', SYS.AppWidget, [], {
        onInitAppWidget: function(err, appInstance){
            this._wsList = [];
            this.reloadTeamList();
        },
        destructor: function(){
            this._cleanTeamList();
        },
        reloadTeamList: function(){
            var filter = this.get('teamListFilter');

            this.set('waiting', true);
            this.get('appInstance').teamList(filter, function(err, result){
                if (err){
                    return this._onLoadTeamList(null);
                }

                var teamList = result.teamList.get('items'),
                    modules = teamList.toArray('module', {distinct: true});

                NS.uses(modules, 'teamList', function(){
                    this._onLoadTeamList(teamList);
                }, this);
            }, this);
        },
        _onLoadTeamList: function(teamList){
            this.set('waiting', false);
            this.set('teamList', teamList);

            if (!teamList){
                return;
            }

            var tp = this.template,
                wsList = this._cleanTeamList();

            teamList.each(function(team){
                var ownerModule = team.get('module'),
                    RowWidget = Brick.mod[ownerModule].TeamListRowWidget;

                if (!RowWidget){
                    return;
                }

                var w = new RowWidget({
                    boundingBox: tp.append('list', tp.replace('itemWrap')),
                    team: team
                });
                wsList[wsList.length] = w;
            }, this);
        },
        _cleanTeamList: function(){
            var list = this._wsList;
            for (var i = 0; i < list.length; i++){
                list[i].destroy();
            }
            return this._wsList = [];
        },
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget,itemWrap'},
            teamList: {value: null},
            teamListFilter: NS.ATTRIBUTE.teamListFilter,
        },
        CLICKS: {}
    });

};