var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['editor.js']},
        {name: '{C#MODNAME}', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){
    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    var TeamEditorWidgetExt = function(){
    };
    TeamEditorWidgetExt.ATTRS = {
        teamid: {
            value: 0,
            setter: function(val){
                return val | 0;
            }
        },
        team: {value: null},
    };
    TeamEditorWidgetExt.prototype = {
        onInitAppWidget: function(err, appInstance){
            var teamApp = appInstance.getApp('team'),
                teamid = this.get('teamid') | 0,
                team;

            this.set('waiting', true);
            if (teamid === 0){
                team = new (teamApp.get('Team'))({
                    appInstance: teamApp,
                    module: appInstance.get('moduleName')
                });
                this._onLoadTeam(team);
            } else {
                teamApp.team(teamid, function(err, result){
                    team = err ? null : result.team;
                    this._onLoadTeam(result.team);
                }, this);
            }
        },
        _onLoadTeam: function(team){
            this.set('waiting', false);
            this.set('team', team);

            var tp = this.template;

            if (team){
                tp.setValue({
                    title: team.get('title')
                });
            }

            this.onLoadTeam(team);
        },
        onLoadTeam: function(team){
        },
        onFillToJSON: function(data){
            return data;
        },
        toJSON: function(){
            var tp = this.template,
                team = this.get('team'),
                data = {
                    id: this.get('teamid'),
                    module: team.get('module'),
                    title: tp.getValue('title')
                };
            return this.onFillToJSON(data);
        },
        save: function(){
            var teamApp = this.get('appInstance').getApp('team'),
                data = this.toJSON();

            this.set('waiting', true);
            teamApp.teamSave(data, function(err, result){
                this.set('waiting', false);
                this.onSave(err, result);
            }, this);
        },
        onSave: function(err, result){
            if (!err && result && result.teamSave){
                this.go('team.view', result.teamSave.teamid);
            }
        }
    };
    NS.TeamEditorWidgetExt = TeamEditorWidgetExt;

};
