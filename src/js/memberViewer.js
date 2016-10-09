var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['editor.js']},
        {name: '{C#MODNAME}', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var MemberViewerWidgetExt = function(){
    };
    MemberViewerWidgetExt.ATTRS = {
        teamApp: NS.ATTRIBUTE.teamApp,
        team: NS.ATTRIBUTE.team,
        memberid: NS.ATTRIBUTE.memberid,
        policy: NS.ATTRIBUTE.policy,
        member: NS.ATTRIBUTE.member,
    };
    MemberViewerWidgetExt.prototype = {
        buildTData: function(){
            return {
                teamid: this.get('team').get('id'),
                memberid: this.get('memberid')
            };
        },
        onInitAppWidget: function(err, appInstance){
            var teamApp = this.get('teamApp'),
                teamid = this.get('team').get('id'),
                policy = this.get('policy'),
                memberid = this.get('memberid'),
                member;

            this.set('waiting', true);

            teamApp.member(teamid, memberid, policy, function(err, result){
                member = err ? null : result.member;
                this._onLoadMember(result.member);
            }, this);
        },
        _onLoadMember: function(member){
            this.set('waiting', false);
            this.set('member', member);

            if (!member){
                // TODO: show member not found error
                return;
            }
            this.appSourceUpdate();
            this.appTriggerUpdate();
            this.onLoadMember(member);
        },
        onLoadMember: function(member){
        },
    };
    NS.MemberViewerWidgetExt = MemberViewerWidgetExt;
};
