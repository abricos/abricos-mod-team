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
        onInitAppWidget: function(err, appInstance, options){
            this.appURLUpdate();
        },
    };
    NS.TeamListRowWidgetExt = TeamListRowWidgetExt;

    NS.TeamListRowWidget = Y.Base.create('TeamListRowWidget', SYS.AppWidget, [
        NS.TeamListRowWidgetExt
    ], {}, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'item'},
        },
        CLICKS: {}
    });

    var TeamListWidgetExt = function(){
    };
    TeamListWidgetExt.ATTRS = {
        teamApp: NS.ATTRIBUTE.teamApp,
        teamList: {value: null},
    };
    TeamListWidgetExt.prototype = {
        onInitAppWidget: function(err, appInstance){
            this._wsList = [];
            this.reloadTeamList();
        },
        destructor: function(){
            this._cleanTeamList();
        },
        reloadTeamList: function(){
            var appInstance = this.get('appInstance'),
                teamApp = this.get('teamApp'),
                ownerModule = appInstance.get('moduleName'),
                filter = {};

            if (ownerModule !== 'team'){
                filter.ownerModule = ownerModule;
            }

            this.set('waiting', true);
            teamApp.teamList(filter, function(err, result){
                if (err){
                    return this._onLoadTeamList(null);
                }

                var teamList = result.teamList,
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
                return this.onLoadTeamList(null);
            }

            var tp = this.template,
                wsList = this._cleanTeamList();

            teamList.each(function(team){
                var ownerModule = team.get('module'),
                    TeamListRowWidget = Brick.mod[ownerModule].TeamListRowWidget || NS.TeamListRowWidget;

                var w = new TeamListRowWidget({
                    boundingBox: tp.append('list', tp.replace('itemWrap')),
                    team: team
                });
                wsList[wsList.length] = w;
            });

            return this.onLoadTeamList(teamList);
        },
        onLoadTeamList: function(teamList){
        },
        _cleanTeamList: function(){
            var list = this._wsList;
            for (var i = 0; i < list.length; i++){
                list[i].destroy();
            }
            return this._wsList = [];
        },

    };
    NS.TeamListWidgetExt = TeamListWidgetExt;

    NS.TeamListWidget = Y.Base.create('teamListWidget', SYS.AppWidget, [
        NS.TeamListWidgetExt
    ], {}, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget,itemWrap'},
        },
        CLICKS: {}
    });
};